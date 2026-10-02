<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\AssignFloorRequest;
use App\Http\Requests\Manager\ReassignDeliveryRequest;
use App\Http\Resources\Manager\WaiterFloorAssignmentResource;
use App\Http\Resources\Manager\DeliveryTaskResource;
use App\Models\WaiterFloorAssignment;
use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FloorAssignmentController extends Controller
{
    protected function getHotelId(): ?string
    {
        $hotelId = request()->input('hotel_id')
            ?: request()->query('hotel_id')
            ?: request()->header('X-Hotel-ID');

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotel_id
                ?: auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if (!$hotelId) {
            $hotelId = app(TenantContext::class)->getHotelId();
        }

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    /**
     * Get today's waiter-floor assignments.
     */
    public function today(): JsonResponse
    {
        $today = now()->format('Y-m-d');
        $hotelId = $this->getHotelId();

        $assignments = WaiterFloorAssignment::where('assignment_date', $today)
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->with(['waiter.user', 'floor', 'shift', 'assignedBy'])
            ->orderBy('priority')
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Today's assignments retrieved successfully",
            'date' => $today,
            'data' => WaiterFloorAssignmentResource::collection($assignments),
        ]);
    }

    /**
     * Bulk store or update waiter-floor assignments.
     */
    public function store(AssignFloorRequest $request): JsonResponse
    {
        $hotelId = $this->getHotelId();
        $assignmentsData = $request->getAssignments();
        $savedAssignments = [];

        DB::beginTransaction();
        try {
            foreach ($assignmentsData as $data) {
                $shiftId = !empty($data['shift_id']) ? $data['shift_id'] : null;
                $status = $data['status'] ?? 'active';
                $priority = $data['priority'] ?? 'primary';
                $assignmentDate = $data['assignment_date'] ?? now()->format('Y-m-d');
                $isActive = ($status === 'active' || (!empty($data['is_active'])));

                $query = WaiterFloorAssignment::where([
                    'waiter_id' => $data['waiter_id'],
                    'floor_id' => $data['floor_id'],
                    'assignment_date' => $assignmentDate,
                ])->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId));

                if ($shiftId) {
                    $query->where('shift_id', $shiftId);
                } else {
                    $query->whereNull('shift_id');
                }

                $existing = $query->first();

                if ($existing) {
                    $existing->update([
                        'status' => $status,
                        'is_active' => $isActive,
                        'priority' => $priority,
                        'assigned_by' => auth()->id(),
                    ]);
                    $savedAssignments[] = $existing;
                } else {
                    $savedAssignments[] = WaiterFloorAssignment::create([
                        'id' => (string) Str::uuid(),
                        'hotel_id' => $hotelId,
                        'waiter_id' => $data['waiter_id'],
                        'floor_id' => $data['floor_id'],
                        'shift_id' => $shiftId,
                        'assignment_date' => $assignmentDate,
                        'status' => $status,
                        'priority' => $priority,
                        'is_active' => $isActive,
                        'assigned_at' => now(),
                        'assigned_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            $loaded = WaiterFloorAssignment::whereIn('id', collect($savedAssignments)->pluck('id'))
                ->with(['waiter.user', 'floor', 'shift', 'assignedBy'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => count($savedAssignments) . ' assignment(s) created/updated successfully',
                'data' => WaiterFloorAssignmentResource::collection($loaded),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[FloorAssignmentController] store failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign floors: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of floor assignments with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $hotelId = $this->getHotelId();
        $query = WaiterFloorAssignment::with(['waiter.user', 'floor', 'shift', 'assignedBy']);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if ($request->filled('date')) {
            $query->whereDate('assignment_date', $request->input('date'));
        }

        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->input('floor_id'));
        }

        if ($request->filled('waiter_id')) {
            $query->where('waiter_id', $request->input('waiter_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $assignments = $query->orderBy('assignment_date', 'desc')
            ->orderBy('priority')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Assignments retrieved successfully',
            'data' => WaiterFloorAssignmentResource::collection($assignments),
            'pagination' => [
                'total' => $assignments->total(),
                'per_page' => $assignments->perPage(),
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
            ],
        ]);
    }

    /**
     * Update assignment priority or status.
     */
    public function update(Request $request, WaiterFloorAssignment $assignment): JsonResponse
    {
        $validated = $request->validate([
            'priority' => 'nullable|in:primary,secondary,backup',
            'status' => 'nullable|in:active,inactive,completed,cancelled',
            'is_active' => 'nullable|boolean',
        ]);

        $updateData = [];
        if ($request->has('priority')) {
            $updateData['priority'] = $validated['priority'];
        }
        if ($request->has('status')) {
            $updateData['status'] = $validated['status'];
            $updateData['is_active'] = $validated['status'] === 'active';
        }
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
            if (!$request->has('status')) {
                $updateData['status'] = $updateData['is_active'] ? 'active' : 'inactive';
            }
        }

        if (!empty($updateData)) {
            $assignment->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully',
            'data' => new WaiterFloorAssignmentResource($assignment->load(['waiter.user', 'floor', 'shift', 'assignedBy'])),
        ]);
    }

    /**
     * Delete assignment.
     */
    public function destroy(WaiterFloorAssignment $assignment): JsonResponse
    {
        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully',
        ]);
    }

    /**
     * Reassign delivery to a different waiter.
     */
    public function reassignDelivery(ReassignDeliveryRequest $request, DeliveryTask $delivery): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->getReassignmentData();
            $oldWaiterId = $delivery->waiter_id;
            $newWaiterId = $data['waiter_id'];

            $delivery->update([
                'waiter_id' => $newWaiterId,
                'assignment_type' => 'manual',
                'status' => 'assigned',
            ]);

            $oldWaiter = Waiter::find($oldWaiterId);
            if ($oldWaiter && $oldWaiter->current_orders > 0) {
                $oldWaiter->decrement('current_orders');
            }

            $newWaiter = Waiter::find($newWaiterId);
            if ($newWaiter) {
                $newWaiter->increment('current_orders');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery reassigned successfully',
                'data' => new DeliveryTaskResource($delivery->load('waiter', 'floor')),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[FloorAssignmentController] reassignDelivery failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign delivery',
            ], 500);
        }
    }

    /**
     * Floor assignment statistics for a given date.
     */
    public function stats(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $hotelId = $this->getHotelId();

        $baseQuery = WaiterFloorAssignment::whereDate('assignment_date', $date)
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId));

        $totalWaitersCount = Waiter::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->where('status', 'active')
            ->count();

        $stats = [
            'total_assignments' => (clone $baseQuery)->count(),
            'total_floors' => (clone $baseQuery)->distinct('floor_id')->count('floor_id'),
            'total_waiters' => $totalWaitersCount > 0
                ? $totalWaitersCount
                : (clone $baseQuery)->distinct('waiter_id')->count('waiter_id'),
            'primary_assignments' => (clone $baseQuery)->where('priority', 'primary')->count(),
            'secondary_assignments' => (clone $baseQuery)->where('priority', 'secondary')->count(),
            'backup_assignments' => (clone $baseQuery)->where('priority', 'backup')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistics retrieved successfully',
            'date' => $date,
            'data' => $stats,
        ]);
    }
}
