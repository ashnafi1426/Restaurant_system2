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
    /**
     * Get today's floor assignments
     * 
     * GET /api/manager/floors/assignments/today
     */
    public function today(): JsonResponse
    {
        try {
            $today = now()->format('Y-m-d');

            $assignments = WaiterFloorAssignment::where('assignment_date', $today)
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

    /**
     * Assign waiters to floors
     * 
     * POST /api/manager/floors/assignments
     */
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

                    // Check if assignment already exists
                    $existing = WaiterFloorAssignment::where([
                        'waiter_id' => $assignment['waiter_id'],
                        'floor_id' => $assignment['floor_id'],
                        'shift_id' => $assignment['shift_id'],
                        'assignment_date' => $assignment['assignment_date'],
                    ])->first();

                    if ($existing) {
                        // Update existing assignment
                        $existing->update([
                            'priority' => $assignment['priority'],
                            'assigned_by' => auth()->id(),
                        ]);
                        $createdAssignments[] = $existing;
                        \Log::info('[FloorAssignmentController] Assignment updated', [
                            'id' => $existing->id,
                        ]);
                    } else {
                        // Create new assignment
                        $newAssignment = WaiterFloorAssignment::create([
                            'id' => Str::uuid(),
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

    /**
     * Get all assignments (with filters)
     * 
     * GET /api/manager/floors/assignments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WaiterFloorAssignment::with('waiter', 'floor', 'shift');

            // Filter by date
            if ($request->has('date')) {
                $query->whereDate('assignment_date', $request->input('date'));
            }

            // Filter by floor
            if ($request->has('floor_id')) {
                $query->where('floor_id', $request->input('floor_id'));
            }

            // Filter by waiter
            if ($request->has('waiter_id')) {
                $query->where('waiter_id', $request->input('waiter_id'));
            }

            // Filter by status
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

    /**
     * Update assignment priority
     * 
     * PATCH /api/manager/floors/assignments/{id}
     */
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

    /**
     * Delete assignment
     * 
     * DELETE /api/manager/floors/assignments/{id}
     */
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

    /**
     * Manually reassign delivery to different waiter
     * 
     * PATCH /api/manager/deliveries/{id}/reassign
     */
    public function reassignDelivery(ReassignDeliveryRequest $request, DeliveryTask $delivery): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->getReassignmentData();
            $oldWaiterId = $delivery->waiter_id;
            $newWaiterId = $data['waiter_id'];

            // Update delivery task
            $delivery->update([
                'waiter_id' => $newWaiterId,
                'assignment_type' => 'manual',
                'status' => 'assigned', // Reset to assigned state
            ]);

            // Decrease old waiter's current orders
            $oldWaiter = Waiter::find($oldWaiterId);
            if ($oldWaiter && $oldWaiter->current_orders > 0) {
                $oldWaiter->decrement('current_orders');
            }

            // Increase new waiter's current orders
            $newWaiter = Waiter::find($newWaiterId);
            $newWaiter->increment('current_orders');

            // Log the reassignment
            \Log::info('Delivery reassigned', [
                'delivery_id' => $delivery->id,
                'old_waiter_id' => $oldWaiterId,
                'new_waiter_id' => $newWaiterId,
                'reason' => $data['reason'] ?? 'No reason provided',
                'reassigned_by' => auth()->id(),
            ]);

            DB::commit();

            // TODO: Send notifications to both waiters
            // TODO: Create audit log entry

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

    /**
     * Get floor assignment statistics
     * 
     * GET /api/manager/floors/assignments/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));

            $stats = [
                'total_assignments' => WaiterFloorAssignment::whereDate('assignment_date', $date)->count(),
                'total_floors' => WaiterFloorAssignment::whereDate('assignment_date', $date)
                    ->distinct('floor_id')
                    ->count('floor_id'),
                'total_waiters' => WaiterFloorAssignment::whereDate('assignment_date', $date)
                    ->distinct('waiter_id')
                    ->count('waiter_id'),
                'primary_assignments' => WaiterFloorAssignment::whereDate('assignment_date', $date)
                    ->where('priority', 'primary')
                    ->count(),
                'secondary_assignments' => WaiterFloorAssignment::whereDate('assignment_date', $date)
                    ->where('priority', 'secondary')
                    ->count(),
                'backup_assignments' => WaiterFloorAssignment::whereDate('assignment_date', $date)
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
            ], 500);
        }
    }
}
