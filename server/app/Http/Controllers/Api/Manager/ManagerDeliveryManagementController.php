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

class ManagerDeliveryManagementController extends Controller
{
    public function __construct(
        private AutomaticWaiterAssignmentService $assignmentService
    ) {}

    /**
     * Get all deliveries with optional filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DeliveryTask::with([
                'order',
                'waiter.user',
                'floor',
            ]);

            if ($request->has('status')) {
                $status = $request->query('status');
                if (is_array($status)) {
                    $query->whereIn('status', $status);
                } else {
                    $query->where('status', $status);
                }
            }

            if ($request->has('floor_id')) {
                $query->where('floor_id', $request->query('floor_id'));
            }

            if ($request->has('waiter_id')) {
                $query->where('waiter_id', $request->query('waiter_id'));
            }

            if ($request->has('date')) {
                $date = $request->query('date');
                $query->whereDate('assigned_at', $date);
            }
            // Removed default date filter - show all deliveries unless specific date is provided

            $deliveries = $query->orderBy('assigned_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $deliveries->items(),
                'pagination' => [
                    'current_page' => $deliveries->currentPage(),
                    'total' => $deliveries->total(),
                    'per_page' => $deliveries->perPage(),
                    'last_page' => $deliveries->lastPage(),
                ],
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
     */
    public function todaySummary(): JsonResponse
    {
        try {
            $today = \Carbon\Carbon::today();
            $summary = $this->assignmentService->getDeliveryMetrics($today);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error fetching delivery summary', [
                'error' => $e->getMessage(),
            ]);
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
     * Reassign delivery to different waiter
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

            Log::info('Delivery reassignment started', [
                'delivery_id' => $deliveryId,
                'old_waiter_id' => $delivery->waiter_id,
                'new_waiter_id' => $newWaiter->id,
            ]);

            DB::beginTransaction();

            if (!$newWaiter->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'New waiter is not available',
                ], 422);
            }

            if ($newWaiter->current_orders >= $newWaiter->maximum_orders) {
                return response()->json([
                    'success' => false,
                    'message' => 'New waiter is at maximum capacity',
                ], 422);
            }

            $oldWaiterId = $delivery->waiter_id;
            $oldWaiter = $oldWaiterId ? Waiter::find($oldWaiterId) : null;

            $delivery->update([
                'waiter_id' => $newWaiter->id,
                'assignment_type' => 'manual',
                'assigned_by' => $managerId,
                'remarks' => $reason,
            ]);

            if ($oldWaiter) {
                $oldWaiter->decrement('current_orders');
            }
            $newWaiter->increment('current_orders');

            DB::commit();

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
            Log::error('Error reassigning delivery', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign delivery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get delivery report for date range
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $fromDate = $request->query('from_date', today());
            $toDate = $request->query('to_date', today());

            $query = DeliveryTask::where('status', 'delivered')
                ->whereBetween('delivered_at', [$fromDate, "{$toDate} 23:59:59"])
                ->with(['waiter.user', 'floor', 'order']);

            $deliveries = $query->get();

            $report = [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'total_deliveries' => $deliveries->count(),
            ];

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
     */
    public function waitingAssignment(): JsonResponse
    {
        try {
            $waiting = DeliveryTask::where('status', 'waiting_assignment')
                ->with(['order.guest', 'order.room', 'floor'])
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
                ], 422);
            }

            $waiter = Waiter::findOrFail($request->input('waiter_id'));

            DB::beginTransaction();

            if (!$waiter->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected waiter is not available',
                ], 422);
            }

            $delivery->update([
                'waiter_id' => $waiter->id,
                'status' => 'assigned',
                'assignment_type' => 'manual',
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            $waiter->increment('current_orders');

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

            if ($delivery->waiter_id) {
                Waiter::find($delivery->waiter_id)?->decrement('current_orders');
            }

            $delivery->cancel('Cancelled by manager');

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
