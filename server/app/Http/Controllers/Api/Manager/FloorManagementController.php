<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreFloorRequest;
use App\Http\Requests\Manager\UpdateFloorRequest;
use App\Http\Resources\Manager\FloorResource;
use App\Models\Floor;
use App\Models\HotelShift;
use App\Services\Manager\FloorManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FloorManagementController extends Controller
{
    protected FloorManagementService $floorService;

    public function __construct(FloorManagementService $floorService)
    {
        $this->floorService = $floorService;
    }

    /**
     * Display a listing of floors.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 100);
        $filters = $request->only(['is_active', 'search']);

        $floors = $this->floorService->listFloors($filters, $perPage);

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
    }

    /**
     * Store a newly created floor.
     */
    public function store(StoreFloorRequest $request): JsonResponse
    {
        $floor = $this->floorService->createFloor($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Floor created successfully',
            'data' => new FloorResource($floor),
        ], 201);
    }

    /**
     * Display the specified floor.
     */
    public function show(Floor $floor): JsonResponse
    {
        $floor->load('rooms', 'waiters');

        return response()->json([
            'success' => true,
            'message' => 'Floor details retrieved successfully',
            'data' => new FloorResource($floor),
        ]);
    }

    /**
     * Update the specified floor.
     */
    public function update(UpdateFloorRequest $request, Floor $floor): JsonResponse
    {
        $updatedFloor = $this->floorService->updateFloor($floor, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Floor updated successfully',
            'data' => new FloorResource($updatedFloor),
        ]);
    }

    /**
     * Remove the specified floor.
     */
    public function destroy(Floor $floor): JsonResponse
    {
        try {
            $this->floorService->deleteFloor($floor);

            return response()->json([
                'success' => true,
                'message' => 'Floor deleted successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Deactivate the specified floor.
     */
    public function deactivate(Floor $floor): JsonResponse
    {
        $deactivated = $this->floorService->deactivateFloor($floor);

        return response()->json([
            'success' => true,
            'message' => 'Floor deactivated successfully',
            'data' => new FloorResource($deactivated),
        ]);
    }

    /**
     * Activate the specified floor.
     */
    public function activate(Floor $floor): JsonResponse
    {
        $activated = $this->floorService->activateFloor($floor);

        return response()->json([
            'success' => true,
            'message' => 'Floor activated successfully',
            'data' => new FloorResource($activated),
        ]);
    }

    /**
     * Retrieve statistics for the specified floor.
     */
    public function stats(Floor $floor): JsonResponse
    {
        $stats = $this->floorService->getFloorStats($floor);

        return response()->json([
            'success' => true,
            'message' => 'Floor statistics retrieved',
            'data' => $stats,
        ]);
    }

    /**
     * Retrieve active shifts for floor scheduling.
     */
    public function shifts(Request $request): JsonResponse
    {
        $shifts = HotelShift::where('is_active', true)
            ->orderBy('start_time')
            ->get(['id', 'name', 'start_time', 'end_time', 'is_active', 'description']);

        return response()->json([
            'success' => true,
            'data' => $shifts,
        ]);
    }
}
