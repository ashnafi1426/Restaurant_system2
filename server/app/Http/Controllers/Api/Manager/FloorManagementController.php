<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\Manager\FloorResource;
use App\Models\HotelFloor;
use App\Services\Manager\FloorManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * FloorManagementController
 * 
 * Handles hotel floor management:
 * - Create/Update floors
 * - Activate/Deactivate floors
 * - View floor details and statistics
 */
class FloorManagementController extends Controller
{
    protected FloorManagementService $floorService;

    public function __construct(FloorManagementService $floorService)
    {
        $this->floorService = $floorService;
    }

    /**
     * Get all floors
     * 
     * GET /api/manager/floors
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = HotelFloor::query();

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by name or floor_number
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('floor_number', '=', (int)$search);
                });
            }

            $perPage = $request->input('per_page', 20);
            $floors = $query->orderBy('floor_number')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Floors retrieved successfully',
                'data' => FloorResource::collection($floors),
                'pagination' => [
                    'total' => $floors->total(),
                    'per_page' => $floors->perPage(),
                    'current_page' => $floors->currentPage(),
                    'last_page' => $floors->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error retrieving floors', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve floors: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new floor
     * 
     * POST /api/manager/floors
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'floor_number' => 'required|integer|unique:hotel_floors,floor_number',
                'name' => 'required|string|max:100|unique:hotel_floors,name',
                'description' => 'nullable|string|max:500',
            ]);

            $floor = HotelFloor::create([
                'id' => Str::uuid(),
                'floor_number' => $validated['floor_number'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            \Log::info('Floor created successfully', [
                'floor_id' => $floor->id,
                'floor_number' => $floor->floor_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Floor created successfully',
                'data' => new FloorResource($floor),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating floor', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create floor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single floor details
     * 
     * GET /api/manager/floors/{id}
     */
    public function show(HotelFloor $floor): JsonResponse
    {
        try {
            $floor->load('rooms', 'waiters');

            return response()->json([
                'success' => true,
                'message' => 'Floor details retrieved successfully',
                'data' => new FloorResource($floor),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve floor details',
            ], 500);
        }
    }

    /**
     * Update floor
     * 
     * PUT /api/manager/floors/{id}
     */
    public function update(Request $request, HotelFloor $floor): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:100|unique:hotel_floors,name,' . $floor->id,
                'description' => 'nullable|string|max:500',
                'is_active' => 'sometimes|boolean',
            ]);

            $floor->update($request->only('name', 'description', 'is_active'));

            \Log::info('Floor updated successfully', [
                'floor_id' => $floor->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Floor updated successfully',
                'data' => new FloorResource($floor),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update floor',
            ], 500);
        }
    }

    /**
     * Delete floor
     * 
     * DELETE /api/manager/floors/{id}
     */
    public function destroy(HotelFloor $floor): JsonResponse
    {
        try {
            // Check if floor has active assignments
            $activeAssignments = $floor->assignments()->where('status', 'active')->count();
            if ($activeAssignments > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete floor with active assignments',
                ], 422);
            }

            $floor->delete();

            \Log::info('Floor deleted successfully', [
                'floor_id' => $floor->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Floor deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete floor',
            ], 500);
        }
    }

    /**
     * Deactivate floor
     * 
     * PATCH /api/manager/floors/{id}/deactivate
     */
    public function deactivate(HotelFloor $floor): JsonResponse
    {
        try {
            $this->floorService->deactivateFloor($floor->id);

            return response()->json([
                'success' => true,
                'message' => 'Floor deactivated successfully',
                'data' => new FloorResource($floor->refresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate floor
     * 
     * PATCH /api/manager/floors/{id}/activate
     */
    public function activate(HotelFloor $floor): JsonResponse
    {
        try {
            $floor->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Floor activated successfully',
                'data' => new FloorResource($floor),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate floor',
            ], 500);
        }
    }

    /**
     * Get floor statistics
     * 
     * GET /api/manager/floors/{id}/stats
     */
    public function stats(HotelFloor $floor): JsonResponse
    {
        try {
            $stats = $this->floorService->getFloorStats($floor->id);

            return response()->json([
                'success' => true,
                'message' => 'Floor statistics retrieved',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
            ], 500);
        }
    }
}
