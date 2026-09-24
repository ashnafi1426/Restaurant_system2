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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FloorAssignmentController extends Controller
{
    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: app(\App\Services\TenantContext::class)->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if ($hotelId) {
            app(\App\Services\TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function today(): JsonResponse
    {
        try {
            $today = now()->format('Y-m-d');
            $hotelId = $this->getHotelId();

            $assignments = WaiterFloorAssignment::where('assignment_date', $today)
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->with('waiter', 'floor', 'shift')
                ->orderBy('priority')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Today\'s assignments retrieved successfully',
                'date' => $today,
                'data' => WaiterFloorAssignmentResource::collection($assignments),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error retrieving today\'s assignments', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assignments',
            ], 500);
        }
    }

    public function store(AssignFloorRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $assignments = $request->getAssignments();
            $createdAssignments = [];
            $errors = [];

            \Log::info('[FloorAssignmentController] Processing assignments', [
                'count' => count($assignments),
                'assignments' => $assignments,
            ]);

            foreach ($assignments as $assignment) {
                try {
                    \Log::info('[FloorAssignmentController] Processing assignment', [
                        'waiter_id' => $assignment['waiter_id'],
                        'floor_id' => $assignment['floor_id'],
                        'shift_id' => $assignment['shift_id'],
                    ]);

                    $hotelId = $this->getHotelId();

                    $existing = WaiterFloorAssignment::where([
                        'waiter_id' => $assignment['waiter_id'],
                        'floor_id' => $assignment['floor_id'],
                        'shift_id' => $assignment['shift_id'],
                        'assignment_date' => $assignment['assignment_date'],
                    ])->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))->first();

                    if ($existing) {
                        $existing->update([
                            'priority' => $assignment['priority'],
                            'assigned_by' => auth()->id(),
                        ]);
                        $createdAssignments[] = $existing;
                        \Log::info('[FloorAssignmentController] Assignment updated', [
                            'id' => $existing->id,
                        ]);
                    } else {
                        $newAssignment = WaiterFloorAssignment::create([
                            'id' => Str::uuid(),
                            'hotel_id' => $hotelId,
                            'waiter_id' => $assignment['waiter_id'],
                            'floor_id' => $assignment['floor_id'],
                            'shift_id' => $assignment['shift_id'],
                            'assignment_date' => $assignment['assignment_date'],
                            'status' => 'active',
                            'priority' => $assignment['priority'],
                            'assigned_by' => auth()->id(),
                        ]);
                        $createdAssignments[] = $newAssignment;
                        \Log::info('[FloorAssignmentController] Assignment created', [
                            'id' => $newAssignment->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('[FloorAssignmentController] Assignment creation failed', [
                        'waiter_id' => $assignment['waiter_id'],
                        'floor_id' => $assignment['floor_id'],
                        'shift_id' => $assignment['shift_id'],
                        'assignment_date' => $assignment['assignment_date'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $errors[] = [
                        'waiter_id' => $assignment['waiter_id'],
                        'floor_id' => $assignment['floor_id'],
                        'shift_id' => $assignment['shift_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            \Log::info('[FloorAssignmentController] Assignments processed', [
                'created' => count($createdAssignments),
                'errors' => count($errors),
            ]);

            $response = [
                'success' => count($errors) === 0,
                'message' => count($createdAssignments) . ' assignment(s) created/updated successfully',
                'data' => WaiterFloorAssignmentResource::collection(
                    WaiterFloorAssignment::whereIn('id', collect($createdAssignments)->pluck('id'))
                        ->with('waiter', 'floor', 'shift')
                        ->get()
                ),
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('[FloorAssignmentController] Error assigning floors', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign floors: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $query = WaiterFloorAssignment::with('waiter', 'floor', 'shift');

            if ($hotelId) {
                $query->where('hotel_id', $hotelId);
            }

            if ($request->has('date')) {
                $query->whereDate('assignment_date', $request->input('date'));
            }

            if ($request->has('floor_id')) {
                $query->where('floor_id', $request->input('floor_id'));
            }

            if ($request->has('waiter_id')) {
                $query->where('waiter_id', $request->input('waiter_id'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $perPage = $request->input('per_page', 20);
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assignments',
            ], 500);
        }
    }

    public function update(Request $request, WaiterFloorAssignment $assignment): JsonResponse
    {
        try {
            $request->validate([
                'priority' => 'required|in:primary,secondary,backup',
            ]);

            $assignment->update([
                'priority' => $request->input('priority'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => new WaiterFloorAssignmentResource($assignment->load('waiter', 'floor', 'shift')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment',
            ], 500);
        }
    }

    public function destroy(WaiterFloorAssignment $assignment): JsonResponse
    {
        try {
            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment',
            ], 500);
        }
    }

    public function reassignDelivery(ReassignDeliveryRequest $request, DeliveryTask $delivery): JsonResponse
    {
        try {
            DB::beginTransaction();

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
            $newWaiter->increment('current_orders');

            \Log::info('Delivery reassigned', [
                'delivery_id' => $delivery->id,
                'old_waiter_id' => $oldWaiterId,
                'new_waiter_id' => $newWaiterId,
                'reason' => $data['reason'] ?? 'No reason provided',
                'reassigned_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery reassigned successfully',
                'data' => new DeliveryTaskResource($delivery->load('waiter', 'floor')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error reassigning delivery', [
                'delivery_id' => $delivery->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign delivery',
            ], 500);
        }
    }

    public function stats(Request $request): JsonResponse
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $hotelId = $this->getHotelId();

            $baseQuery = WaiterFloorAssignment::whereDate('assignment_date', $date)
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId));

            $totalWaitersCount = \App\Models\Waiter::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->where('status', 'active')
                ->count();

            $stats = [
                'total_assignments' => (clone $baseQuery)->count(),
                'total_floors' => (clone $baseQuery)
                    ->distinct('floor_id')
                    ->count('floor_id'),
                'total_waiters' => $totalWaitersCount > 0 ? $totalWaitersCount : (clone $baseQuery)->distinct('waiter_id')->count('waiter_id'),
                'primary_assignments' => (clone $baseQuery)
                    ->where('priority', 'primary')
                    ->count(),
                'secondary_assignments' => (clone $baseQuery)
                    ->where('priority', 'secondary')
                    ->count(),
                'backup_assignments' => (clone $baseQuery)
                    ->where('priority', 'backup')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'date' => $date,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('FloorAssignmentController stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
            ], 500);
        }
    }
}
