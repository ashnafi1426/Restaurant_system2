<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomTypeRequest;
use App\Http\Requests\UpdateRoomTypeRequest;
use App\Http\Resources\RoomTypeResource;
use App\Models\RoomType;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class RoomTypeController extends Controller
{
    /**
     * Display a listing of room types with search and status filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $hotelId = TenantContext::id()
            ?: $request->input('hotel_id')
            ?: $request->header('X-Hotel-ID')
            ?: $request->header('x-hotel-id')
            ?: auth()->user()?->hotel_id;

        $query = RoomType::query()->withCount('rooms');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $roomTypes = $query->latest()->paginate($perPage);

        return RoomTypeResource::collection($roomTypes);
    }

    /**
     * Store a newly created room type.
     */
    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        try {
            $hotelId = TenantContext::id()
                ?: $request->input('hotel_id')
                ?: $request->header('X-Hotel-ID')
                ?: $request->header('x-hotel-id');

            $validated = $request->validated();
            $validated['hotel_id'] = $hotelId;
            $validated['amenities'] = $validated['amenities'] ?? [];
            $validated['is_active'] = $validated['is_active'] ?? true;

            $roomType = RoomType::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Room type created successfully.',
                'data' => new RoomTypeResource($roomType),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create room type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified room type with room counts.
     */
    public function show(RoomType $roomType): JsonResponse
    {
        $roomType->loadCount('rooms');

        return response()->json([
            'success' => true,
            'message' => 'Room type retrieved successfully.',
            'data' => new RoomTypeResource($roomType),
        ]);
    }

    /**
     * Update the specified room type.
     */
    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): JsonResponse
    {
        try {
            $roomType->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Room type updated successfully.',
                'data' => new RoomTypeResource($roomType),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to update room type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified room type from storage.
     */
    public function destroy(RoomType $roomType): JsonResponse
    {
        try {
            if ($roomType->rooms()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this room type because it has assigned rooms.',
                ], 422);
            }

            $roomType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room type deleted successfully.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete room type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}