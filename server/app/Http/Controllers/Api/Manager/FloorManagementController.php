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
use Illuminate\Validation\Rule;

class FloorManagementController extends Controller
{
    protected FloorManagementService $floorService;

    public function __construct(FloorManagementService $floorService)
    {
        $this->floorService = $floorService;
    }

    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: request()->header('x-hotel-id')
            ?: request()->input('hotel_id')
            ?: request()->query('hotel_id')
            ?: app(\App\Services\TenantContext::class)->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if (!$hotelId) {
            $hotelId = \App\Models\Hotel::first()?->id;
        }

        if ($hotelId) {
            app(\App\Services\TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            // Auto-discover / backfill existing floors from rooms or defaults
            if ($hotelId) {
                $floorsCount = HotelFloor::withoutTenant()->where('hotel_id', $hotelId)->count();
                if ($floorsCount === 0) {
                    // Check if there are existing rooms with floor numbers
                    $existingRoomFloors = \App\Models\Room::withoutTenant()
                        ->where('hotel_id', $hotelId)
                        ->whereNotNull('floor')
                        ->distinct()
                        ->pluck('floor');

                    foreach ($existingRoomFloors as $flNum) {
                        $num = (int)$flNum;
                        if ($num > 0) {
                            $created = HotelFloor::withoutTenant()->firstOrCreate(
                                ['hotel_id' => $hotelId, 'floor_number' => $num],
                                ['name' => "Floor {$num}", 'description' => "Floor {$num}", 'is_active' => true]
                            );
                            \App\Models\Room::withoutTenant()
                                ->where('hotel_id', $hotelId)
                                ->where('floor', $num)
                                ->whereNull('floor_id')
                                ->update(['floor_id' => $created->id]);
                        }
                    }

                    // Also check if there are floors with hotel_id null
                    $nullFloors = HotelFloor::withoutTenant()->whereNull('hotel_id')->get();
                    if ($nullFloors->isNotEmpty()) {
                        foreach ($nullFloors as $nf) {
                            HotelFloor::withoutTenant()->firstOrCreate(
                                ['hotel_id' => $hotelId, 'floor_number' => $nf->floor_number],
                                ['name' => $nf->name, 'description' => $nf->description ?? null, 'is_active' => true]
                            );
                        }
                    }

                    // If still empty, create standard Floor 1
                    if (HotelFloor::withoutTenant()->where('hotel_id', $hotelId)->count() === 0) {
                        HotelFloor::withoutTenant()->firstOrCreate(
                            ['hotel_id' => $hotelId, 'floor_number' => 1],
                            ['name' => 'Floor 1', 'description' => 'First Floor', 'is_active' => true]
                        );
                    }
                }
            }

            $query = HotelFloor::query()->withCount('rooms');

            if ($hotelId) {
                $query->where('hotel_id', $hotelId);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('floor_number', '=', (int)$search);
                });
            }

            $perPage = $request->input('per_page', 100);
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

    public function store(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $validated = $request->validate([
                'floor_number' => [
                    'required',
                    'integer',
                    $hotelId 
                        ? Rule::unique('floors', 'floor_number')->where('hotel_id', $hotelId)
                        : 'unique:floors,floor_number',
                ],
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    $hotelId 
                        ? Rule::unique('floors', 'name')->where('hotel_id', $hotelId)
                        : 'unique:floors,name',
                ],
                'description' => 'nullable|string|max:500',
            ]);

            $floor = HotelFloor::create([
                'id' => Str::uuid(),
                'hotel_id' => $hotelId,
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

    public function update(Request $request, HotelFloor $floor): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:100|unique:floors,name,' . $floor->id,
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

    public function destroy(HotelFloor $floor): JsonResponse
    {
        try {
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

    public function shifts(Request $request): JsonResponse
    {
        try {
            $shifts = \App\Models\HotelShift::where('is_active', true)
                ->orderBy('start_time')
                ->get()
                ->map(function ($shift) {
                    return [
                        'id' => $shift->id,
                        'name' => $shift->name,
                        'start_time' => $shift->start_time,
                        'end_time' => $shift->end_time,
                        'is_active' => $shift->is_active,
                        'description' => $shift->description,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $shifts,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching shifts', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shifts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
