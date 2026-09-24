<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Http\Resources\TaxRateResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TaxRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TaxRate::query();

        // Active filter
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Applies to filter
        if ($request->has('applies_to')) {
            $query->where(function ($q) use ($request) {
                $q->where('applies_to', $request->applies_to)
                  ->orWhere('applies_to', 'general');
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('is_default', 'desc')
              ->orderBy('rate', 'desc');

        if ($request->boolean('all', false) || $request->has('no_paginate')) {
            return TaxRateResource::collection($query->get());
        }

        $perPage = $request->input('per_page', 15);
        $taxRates = $query->paginate($perPage);

        return TaxRateResource::collection($taxRates);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:percentage,fixed,vat,service'],
            'rate' => ['required', 'numeric', 'min:0'],
            'applies_to' => ['required', 'string', 'in:all,food,beverage,food_beverage,room,service,general'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $hotelId = $request->header('X-Hotel-ID') 
            ?? app(\App\Services\TenantContext::class)->getHotelId();

        // If setting as default, unset previous defaults of same applies_to
        if (!empty($validated['is_default']) && $validated['is_default']) {
            TaxRate::where('applies_to', $validated['applies_to'])
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
            'message' => 'Tax rate created successfully',
            'data' => new TaxRateResource($taxRate),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $taxRate = TaxRate::withCount('menuItems')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new TaxRateResource($taxRate),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $taxRate = TaxRate::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['sometimes', 'required', 'string', 'in:percentage,fixed,vat,service'],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0'],
            'applies_to' => ['sometimes', 'required', 'string', 'in:all,food,beverage,food_beverage,room,service,general'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if (!empty($validated['is_default']) && $validated['is_default']) {
            $appliesTo = $validated['applies_to'] ?? $taxRate->applies_to;
            TaxRate::where('applies_to', $appliesTo)
                ->where('id', '!=', $taxRate->id)
                ->update(['is_default' => false]);
        }

        $taxRate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate updated successfully',
            'data' => new TaxRateResource($taxRate->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $taxRate = TaxRate::findOrFail($id);

        // Check if attached to menu items
        if ($taxRate->menuItems()->exists()) {
            // Deactivate instead of hard delete to preserve historical integrity
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
            'message' => 'Tax rate deleted successfully',
        ]);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus($id)
    {
        $taxRate = TaxRate::findOrFail($id);
        $taxRate->is_active = !$taxRate->is_active;
        $taxRate->save();

        return response()->json([
            'success' => true,
            'message' => $taxRate->is_active ? 'Tax rate activated' : 'Tax rate deactivated',
            'data' => new TaxRateResource($taxRate),
        ]);
    }
}
