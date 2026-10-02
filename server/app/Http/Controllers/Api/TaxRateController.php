<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxRateRequest;
use App\Http\Requests\UpdateTaxRateRequest;
use App\Http\Resources\TaxRateResource;
use App\Models\TaxRate;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Throwable;

class TaxRateController extends Controller
{
    /**
     * Display a listing of tax rates scoped to the current hotel.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $hotelId = TenantContext::id();

        $query = TaxRate::query();

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('applies_to')) {
            $appliesTo = $request->input('applies_to');
            $query->where(function ($q) use ($appliesTo) {
                $q->where('applies_to', $appliesTo)
                  ->orWhere('applies_to', 'general');
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('is_default')->orderByDesc('rate');

        if ($request->boolean('all', false) || $request->has('no_paginate')) {
            return TaxRateResource::collection($query->get());
        }

        $perPage = (int) $request->input('per_page', 15);

        return TaxRateResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created tax rate.
     */
    public function store(StoreTaxRateRequest $request): JsonResponse
    {
        try {
            $hotelId = TenantContext::id()
                ?: $request->header('X-Hotel-ID')
                ?: $request->header('x-hotel-id');

            $validated = $request->validated();

            if (!empty($validated['is_default'])) {
                TaxRate::where('hotel_id', $hotelId)
                    ->where('applies_to', $validated['applies_to'])
                    ->update(['is_default' => false]);
            }

            $taxRate = TaxRate::create([
                'id' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
                'name' => $validated['name'],
                'code' => $validated['code'] ?? strtoupper(Str::slug($validated['name'], '_')),
                'type' => $validated['type'],
                'rate' => $validated['rate'],
                'applies_to' => $validated['applies_to'],
                'is_active' => $validated['is_active'] ?? true,
                'is_default' => $validated['is_default'] ?? false,
                'description' => $validated['description'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tax rate created successfully.',
                'data' => new TaxRateResource($taxRate),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create tax rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified tax rate.
     */
    public function show(TaxRate $taxRate): JsonResponse
    {
        $taxRate->loadCount('menuItems');

        return response()->json([
            'success' => true,
            'data' => new TaxRateResource($taxRate),
        ]);
    }

    /**
     * Update the specified tax rate.
     */
    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (!empty($validated['is_default'])) {
                $appliesTo = $validated['applies_to'] ?? $taxRate->applies_to;
                TaxRate::where('hotel_id', $taxRate->hotel_id)
                    ->where('applies_to', $appliesTo)
                    ->where('id', '!=', $taxRate->id)
                    ->update(['is_default' => false]);
            }

            $taxRate->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tax rate updated successfully.',
                'data' => new TaxRateResource($taxRate->fresh()),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to update tax rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified tax rate from storage or deactivate if in use.
     */
    public function destroy(TaxRate $taxRate): JsonResponse
    {
        try {
            if ($taxRate->menuItems()->exists()) {
                $taxRate->update(['is_active' => false]);

                return response()->json([
                    'success' => true,
                    'message' => 'Tax rate is assigned to existing menu items and was deactivated instead of deleted.',
                    'data' => new TaxRateResource($taxRate),
                ]);
            }

            $taxRate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tax rate deleted successfully.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete tax rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active status of a tax rate.
     */
    public function toggleStatus(TaxRate $taxRate): JsonResponse
    {
        try {
            $taxRate->update(['is_active' => !$taxRate->is_active]);

            return response()->json([
                'success' => true,
                'message' => $taxRate->is_active ? 'Tax rate activated.' : 'Tax rate deactivated.',
                'data' => new TaxRateResource($taxRate),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to toggle tax rate status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
