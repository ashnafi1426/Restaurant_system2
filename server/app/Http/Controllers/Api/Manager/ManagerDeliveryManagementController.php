<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use App\Events\DeliveryReassignedEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ManagerDeliveryManagementController
 * 
 * Handles manager operations on delivery tasks:
 * - View all deliveries (with filters)
 * - Get delivery details
 * - Reassign delivery to different waiter
 * - View delivery metrics and reports
 * - Handle manual assignments
 * 
 * STEP 16: Manager Override functionality
 */
class ManagerDeliveryManagementController extends Controller
{
    public function __construct(
        private AutomaticWaiterAssignmentService $assignmentService
    ) {}

    /**
     * Get all deliveries with optional filters
     * 
     * Query params:
     * - status: assigned, accepted, picked_up, on_delivery, delivered, cancelled, waiting_assignment
     * - floor_id: filter by floor
     * - waiter_id: filter by waiter
     * - date: YYYY-MM-DD
     * - page: pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DeliveryTask::with([
                'order',
                'waiter.user',
                'floor',
            ]);

            // Filter by status
            if ($request->has('status')) {
                $status = $request->query('status');
                if (is_array($status)) {
                    $query->whereIn('status', $status);
                } else {
                    $query->where('status', $status);
                }
            }

            // Filter by floor
            if ($request->has('floor_id')) {
                $query->where('floor_id', $request->query('floor_id'));
            }

            // Filter by waiter
            if ($request->has('waiter_id')) {
                $query->where('waiter_id', $request->query('waiter_id'));
            }

            // Filter by date
            if ($request->has('date')) {
                $date = $request->query('date');
                $query->whereDate('assigned_at', $date);
            } else {
                // Default to today if no date specified
                $query->whereDate('assigned_at', today());
            }

            // Order by assigned time descending
            $deliveries = $query->orderBy('assigned_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $deliveries,
                'total' => $deliveries->total(),
                'page' => $deliveries->currentPage(),
                'per_page' => $deliveries->perPage(),
            ]);

        } catch (\Throwable $e) {
            Log::error('Error fetching deliveries', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deliveries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's delivery summary
     * 
     * Returns:
     * - Total deliveries
     * - Automatic vs manual assignments
     * - Delivered count
     * - Rejected count
     * - Waiting assignment count
     * - Average delivery time
     */
    public function todaySummary(): JsonResponse
    {
        try {
            $summary = $this->assignmentService->getDeliveryMetrics(today());

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error fetching delivery summary', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed delivery information
     */
    public function show(string $deliveryId): JsonResponse
    {
        try {
            $delivery = DeliveryTask::with([
                'order.guest',
                'order.room',
                'order.orderItems.menuItem',
                'waiter.user',
                'floor',
                'assignedBy.user',
            ])->findOrFail($deliveryId);

            // Calculate delivery metrics if delivered
            $metrics = null;
            if ($delivery->status === 'delivered' && $delivery->assigned_at && $delivery->delivered_at) {
                $metrics = [
                    'duration_minutes' => $delivery->assigned_at->diffInMinutes($delivery->delivered_at),
                    'is_late' => $delivery->isLate(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'delivery' => $delivery,
                    'metrics' => $metrics,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error fetching delivery details', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * STEP 16: Reassign delivery to different waiter
     * 
     * Request body:
     * {
     *   "waiter_id": "waiter_uuid",
     *   "reason": "Reason for reassignment"
     * }
     * 
     * Actions:
     * 1. Validate new waiter is available
     * 2. Decrement old waiter's workload
     * 3. Update delivery task
     * 4. Increment new waiter's workload
     * 5. Notify old waiter (delivery reassigned)
     * 6. Notify new waiter (new delivery assigned)
     * 7. Dispatch event for real-time updates
     */
    public function reassign(string $deliveryId, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'waiter_id' => 'required|exists:waiters,id',
                'reason' => 'nullable|string|max:500',
            ]);

            $delivery = DeliveryTask::findOrFail($deliveryId);
            $newWaiter = Waiter::findOrFail($request->input('waiter_id'));
            $reason = $request->input('reason', 'Manager reassignment');
            $managerId = auth()->id();

            Log::info('🔵 [REASSIGN] Starting delivery reassignment', [
                'delivery_id' => $deliveryId,
                'old_waiter_id' => $delivery->waiter_id,
                'new_waiter_id' => $newWaiter->id,
                'reason' => $reason,
                'manager_id' => $managerId,
            ]);

            DB::beginTransaction();

            // Validate new waiter is available
            if (!$newWaiter->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'New waiter is not available',
                    'reason' => "Status: {$newWaiter->status}, Availability: {$newWaiter->availability}",
                ], 422);
            }

            // Check if new waiter has capacity
            if ($newWaiter->current_orders >= $newWaiter->maximum_orders) {
                return response()->json([
                    'success' => false,
                    'message' => 'New waiter is at maximum capacity',
                    'current_orders' => $newWaiter->current_orders,
                    'maximum_orders' => $newWaiter->maximum_orders,
                ], 422);
            }

            // Store old waiter ID for notifications
            $oldWaiterId = $delivery->waiter_id;
            $oldWaiter = $oldWaiterId ? Waiter::find($oldWaiterId) : null;

            // Update delivery task
            $delivery->update([
                'waiter_id' => $newWaiter->id,
                'assignment_type' => 'manual',
                'assigned_by' => $managerId,
                'remarks' => $reason,
            ]);

            // Update workloads
            if ($oldWaiter) {
                $oldWaiter->decrement('current_orders');
            }
            $newWaiter->increment('current_orders');

            Log::info(' [REASSIGN] Delivery reassigned successfully', [
                'delivery_id' => $deliveryId,
                'old_waiter_id' => $oldWaiterId,
                'new_waiter_id' => $newWaiter->id,
                'new_workload' => $newWaiter->current_orders,
            ]);

            DB::commit();

            // Dispatch event for real-time updates
            DeliveryReassignedEvent::dispatch($delivery, $oldWaiter, $newWaiter, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Delivery reassigned successfully',
                'data' => [
                    'delivery' => $delivery->fresh(),
                    'old_waiter' => $oldWaiter,
                    'new_waiter' => $newWaiter,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [REASSIGN] Error reassigning delivery', [
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign delivery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get delivery report for date range
     * 
     * Query params:
     * - from_date: YYYY-MM-DD
     * - to_date: YYYY-MM-DD
     * - include_metrics: boolean
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $fromDate = $request->query('from_date', today());
            $toDate = $request->query('to_date', today());
            $includeMetrics = $request->query('include_metrics', true);

            $query = DeliveryTask::where('status', 'delivered')
                ->whereBetween('delivered_at', [$fromDate, "{$toDate} 23:59:59"])
                ->with(['waiter.user', 'floor', 'order']);

            $deliveries = $query->get();

            $report = [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'total_deliveries' => $deliveries->count(),
                'total_value' => $deliveries->sum(fn ($d) => $d->order->total ?? 0),
            ];

            if ($includeMetrics) {
                $report['metrics'] = [
                    'average_delivery_time' => round($deliveries->average(function ($task) {
                        return $task->assigned_at && $task->delivered_at
                            ? $task->assigned_at->diffInMinutes($task->delivered_at)
                            : 0;
                    }), 2),
                    'late_deliveries' => $deliveries->filter(fn ($d) => $d->isLate())->count(),
                    'on_time_percentage' => round(
                        ((($deliveries->count() - $deliveries->filter(fn ($d) => $d->isLate())->count()) / max($deliveries->count(), 1)) * 100),
                        2
                    ),
                ];

                // Group by waiter
                $report['by_waiter'] = $deliveries
                    ->groupBy('waiter_id')
                    ->map(function ($group) {
                        $late = $group->filter(fn ($d) => $d->isLate())->count();
                        $total = $group->count();

                        return [
                            'waiter_id' => $group->first()->waiter_id,
                            'waiter_name' => $group->first()->waiter->user->name,
                            'total_deliveries' => $total,
                            'on_time' => $total - $late,
                            'late' => $late,
                            'on_time_percentage' => round((($total - $late) / max($total, 1)) * 100, 2),
                        ];
                    })
                    ->values();
            }

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error generating delivery report', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get deliveries waiting for manual assignment
     * These are orders where no waiter was automatically available
     */
    public function waitingAssignment(): JsonResponse
    {
        try {
            $waiting = DeliveryTask::where('status', 'waiting_assignment')
                ->with([
                    'order.guest',
                    'order.room',
                    'floor',
                ])
                ->orderBy('assigned_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'count' => $waiting->count(),
                'data' => $waiting,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error fetching waiting assignments', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waiting assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually assign waiting delivery to a waiter
     * 
     * Used when manager decides to assign a delivery that was waiting
     */
    public function manuallyAssign(string $deliveryId, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'waiter_id' => 'required|exists:waiters,id',
            ]);

            $delivery = DeliveryTask::findOrFail($deliveryId);

            if ($delivery->status !== 'waiting_assignment') {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery is not waiting for assignment',
                    'current_status' => $delivery->status,
                ], 422);
            }

            $waiter = Waiter::findOrFail($request->input('waiter_id'));

            DB::beginTransaction();

            // Validate waiter availability
            if (!$waiter->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected waiter is not available',
                ], 422);
            }

            // Assign delivery
            $delivery->update([
                'waiter_id' => $waiter->id,
                'status' => 'assigned',
                'assignment_type' => 'manual',
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            $waiter->increment('current_orders');

            Log::info('Manual assignment created', [
                'delivery_id' => $deliveryId,
                'waiter_id' => $waiter->id,
                'manager_id' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery assigned successfully',
                'data' => $delivery->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error manually assigning delivery', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign delivery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete/cancel a delivery
     */
    public function destroy(string $deliveryId): JsonResponse
    {
        try {
            $delivery = DeliveryTask::findOrFail($deliveryId);

            if ($delivery->status === 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel delivered orders',
                ], 422);
            }

            DB::beginTransaction();

            // Decrement waiter's workload if assigned
            if ($delivery->waiter_id) {
                Waiter::find($delivery->waiter_id)?->decrement('current_orders');
            }

            // Cancel delivery
            $delivery->cancel('Cancelled by manager');

            Log::info('Delivery cancelled by manager', [
                'delivery_id' => $deliveryId,
                'manager_id' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery cancelled successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling delivery', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel delivery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
