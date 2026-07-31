<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\Manager\ShiftResource;
use App\Models\HotelShift;
use App\Services\Manager\ShiftManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ShiftManagementController
 * 
 * Handles hotel shift management:
 * - Create/Update shifts
 * - Activate/Deactivate shifts
 * - Get current shift
 * - View shift statistics
 */
class ShiftManagementController extends Controller
{
    protected ShiftManagementService $shiftService;

    public function __construct(ShiftManagementService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Get all shifts
     * 
     * GET /api/manager/shifts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = HotelShift::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $perPage = $request->input('per_page', 20);
            $shifts = $query->orderBy('start_time')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Shifts retrieved successfully',
                'data' => ShiftResource::collection($shifts),
                'pagination' => [
                    'total' => $shifts->total(),
                    'per_page' => $shifts->perPage(),
                    'current_page' => $shifts->currentPage(),
                    'last_page' => $shifts->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve shifts',
            ], 500);
        }
    }

    /**
     * Create new shift
     * 
     * POST /api/manager/shifts
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:100|unique:hotel_shifts,name',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
            ]);

            $shift = HotelShift::create([
                'id' => Str::uuid(),
                'name' => $request->input('name'),
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'status' => 'active',
                'is_night_shift' => $this->isNightShift(
                    $request->input('start_time'),
                    $request->input('end_time')
                ),
            ]);

            \Log::info('Shift created successfully', [
                'shift_id' => $shift->id,
                'name' => $shift->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shift created successfully',
                'data' => new ShiftResource($shift),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating shift', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create shift',
            ], 500);
        }
    }

    /**
     * Get single shift details
     * 
     * GET /api/manager/shifts/{id}
     */
    public function show(HotelShift $shift): JsonResponse
    {
        try {
            $shift->load('assignments');

            return response()->json([
                'success' => true,
                'message' => 'Shift details retrieved successfully',
                'data' => new ShiftResource($shift),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve shift details',
            ], 500);
        }
    }

    /**
     * Update shift
     * 
     * PUT /api/manager/shifts/{id}
     */
    public function update(Request $request, HotelShift $shift): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:100|unique:hotel_shifts,name,' . $shift->id,
                'start_time' => 'sometimes|date_format:H:i',
                'end_time' => 'sometimes|date_format:H:i',
                'status' => 'sometimes|in:active,inactive',
            ]);

            $data = $request->only('name', 'start_time', 'end_time', 'status');

            // If times are updated, recalculate if it's night shift
            if ($request->has('start_time') || $request->has('end_time')) {
                $startTime = $request->input('start_time', $shift->start_time);
                $endTime = $request->input('end_time', $shift->end_time);
                $data['is_night_shift'] = $this->isNightShift($startTime, $endTime);
            }

            $shift->update($data);

            \Log::info('Shift updated successfully', [
                'shift_id' => $shift->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shift updated successfully',
                'data' => new ShiftResource($shift),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shift',
            ], 500);
        }
    }

    /**
     * Delete shift
     * 
     * DELETE /api/manager/shifts/{id}
     */
    public function destroy(HotelShift $shift): JsonResponse
    {
        try {
            // Check if shift has active assignments
            $activeAssignments = $shift->assignments()->where('status', 'active')->count();
            if ($activeAssignments > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete shift with active assignments',
                ], 422);
            }

            $shift->delete();

            \Log::info('Shift deleted successfully', [
                'shift_id' => $shift->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shift deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete shift',
            ], 500);
        }
    }

    /**
     * Deactivate shift
     * 
     * PATCH /api/manager/shifts/{id}/deactivate
     */
    public function deactivate(HotelShift $shift): JsonResponse
    {
        try {
            $this->shiftService->deactivateShift($shift->id);

            return response()->json([
                'success' => true,
                'message' => 'Shift deactivated successfully',
                'data' => new ShiftResource($shift->refresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate shift
     * 
     * PATCH /api/manager/shifts/{id}/activate
     */
    public function activate(HotelShift $shift): JsonResponse
    {
        try {
            $shift->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Shift activated successfully',
                'data' => new ShiftResource($shift),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate shift',
            ], 500);
        }
    }

    /**
     * Get current shift
     * 
     * GET /api/manager/shifts/current
     */
    public function current(): JsonResponse
    {
        try {
            $shift = $this->shiftService->getCurrentShift();

            if (!$shift) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active shift at this time',
                    'data' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Current shift retrieved',
                'data' => new ShiftResource($shift),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve current shift',
            ], 500);
        }
    }

    /**
     * Get shift statistics
     * 
     * GET /api/manager/shifts/{id}/stats
     */
    public function stats(HotelShift $shift): JsonResponse
    {
        try {
            $stats = [
                'total_assignments' => $shift->assignments()->count(),
                'active_assignments' => $shift->assignments()->where('status', 'active')->count(),
                'total_waiters' => $shift->assignments()->distinct('waiter_id')->count('waiter_id'),
                'total_floors' => $shift->assignments()->distinct('floor_id')->count('floor_id'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Shift statistics retrieved',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
            ], 500);
        }
    }

    /**
     * Helper: Determine if a shift is night shift
     */
    private function isNightShift(string $startTime, string $endTime): bool
    {
        // Convert to minutes for easier comparison
        [$startHour, $startMin] = explode(':', $startTime);
        [$endHour, $endMin] = explode(':', $endTime);

        $startMinutes = (int)$startHour * 60 + (int)$startMin;
        $endMinutes = (int)$endHour * 60 + (int)$endMin;

        // Night shift if it goes past midnight or starts in evening
        return $endMinutes < $startMinutes || $startMinutes >= 18 * 60; // 6 PM onwards
    }
}
