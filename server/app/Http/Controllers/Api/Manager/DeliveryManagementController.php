<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ReassignDeliveryRequest;
use App\Http\Resources\Manager\DeliveryTaskResource;
use App\Models\DeliveryTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DeliveryManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DeliveryTask::with('waiter', 'floor', 'assignedBy');
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->has('waiter_id')) {
                $query->where('waiter_id', $request->input('waiter_id'));
            }
            if ($request->has('floor_id')) {
                $query->where('floor_id', $request->input('floor_id'));
            }
            if ($request->has('start_date')) {
                $query->whereDate('assigned_at', '>=', $request->input('start_date'));
            }
            if ($request->has('end_date')) {
                $query->whereDate('assigned_at', '<=', $request->input('end_date'));
            }
            if ($request->has('assignment_type')) {
                $query->where('assignment_type', $request->input('assignment_type'));
            }
            $sortBy = $request->input('sort_by', 'assigned_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->input('per_page', 20);
            $deliveries = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'message' => 'Deliveries retrieved successfully',
                'data' => DeliveryTaskResource::collection($deliveries),
                'pagination' => [
                    'total' => $deliveries->total(),
                    'per_page' => $deliveries->perPage(),
                    'current_page' => $deliveries->currentPage(),
                    'last_page' => $deliveries->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve deliveries',
            ], 500);
        }
    }

    /**
     * Get single delivery details
     * 
     * GET /api/manager/deliveries/{id}
     */
    public function show(DeliveryTask $delivery): JsonResponse
    {
        try {
            $delivery->load('waiter', 'floor', 'assignedBy');

            return response()->json([
                'success' => true,
                'message' => 'Delivery details retrieved',
                'data' => new DeliveryTaskResource($delivery),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve delivery details',
            ], 500);
        }
    }

    /**
     * Reassign delivery to different waiter
     * 
     * PATCH /api/manager/deliveries/{id}/reassign
     */
    public function reassign(ReassignDeliveryRequest $request, DeliveryTask $delivery): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->getReassignmentData();
            $oldWaiterId = $delivery->waiter_id;
            $newWaiterId = $data['waiter_id'];

            // Verify new waiter exists and is active
            $newWaiter = \App\Models\Waiter::find($newWaiterId);
            if (!$newWaiter || $newWaiter->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'New waiter is not available',
                ], 422);
            }

            // Check new waiter's workload
            if ($newWaiter->current_orders >= $newWaiter->maximum_orders) {
                return response()->json([
                    'success' => false,
                    'message' => 'New waiter has reached maximum order limit',
                ], 422);
            }

            // Update delivery
            $delivery->update([
                'waiter_id' => $newWaiterId,
                'assignment_type' => 'manual',
                'status' => 'assigned',
            ]);

            // Update waiter order counts
            $oldWaiter = \App\Models\Waiter::find($oldWaiterId);
            if ($oldWaiter && $oldWaiter->current_orders > 0) {
                $oldWaiter->decrement('current_orders');
            }
            $newWaiter->increment('current_orders');

            DB::commit();

            \Log::info('Delivery reassigned', [
                'delivery_id' => $delivery->id,
                'old_waiter_id' => $oldWaiterId,
                'new_waiter_id' => $newWaiterId,
                'reason' => $data['reason'],
                'reassigned_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery reassigned successfully',
                'data' => new DeliveryTaskResource($delivery->load('waiter')),
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
     * Cancel delivery
     * 
     * DELETE /api/manager/deliveries/{id}
     */
    public function destroy(Request $request, DeliveryTask $delivery): JsonResponse
    {
        try {
            DB::beginTransaction();

            $reason = $request->input('reason', 'Cancelled by manager');

            // Cannot cancel if already delivered or completed
            if (in_array($delivery->status, ['delivered', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel delivery that is already delivered',
                ], 422);
            }

            // Decrement waiter's current orders
            if ($delivery->waiter) {
                $delivery->waiter->decrement('current_orders');
            }

            // Update delivery status
            $delivery->update([
                'status' => 'cancelled',
                'delivery_notes' => $reason,
            ]);

            DB::commit();

            \Log::info('Delivery cancelled', [
                'delivery_id' => $delivery->id,
                'reason' => $reason,
                'cancelled_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery cancelled successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel delivery',
            ], 500);
        }
    }

    /**
     * Get delivery statistics and report
     * 
     * GET /api/manager/deliveries/report
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            $query = DeliveryTask::whereBetween('assigned_at', [$startDate, $endDate]);

            // Report data
            $report = [
                'total_deliveries' => $query->count(),
                'completed_deliveries' => (clone $query)->where('status', 'delivered')->count(),
                'failed_deliveries' => (clone $query)->where('status', 'cancelled')->count(),
                'pending_deliveries' => (clone $query)->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])->count(),
                'automatic_assignments' => (clone $query)->where('assignment_type', 'automatic')->count(),
                'manual_reassignments' => (clone $query)->where('assignment_type', 'manual')->count(),
                'avg_delivery_time' => $this->getAverageDeliveryTime($startDate, $endDate),
                'by_waiter' => $this->getDeliveriesByWaiter($startDate, $endDate),
                'by_floor' => $this->getDeliveriesByFloor($startDate, $endDate),
                'by_status' => $this->getDeliveriesByStatus($startDate, $endDate),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Delivery report generated',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating delivery report', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
            ], 500);
        }
    }

    /**
     * Get today's delivery summary
     * 
     * GET /api/manager/deliveries/summary/today
     */
    public function todaySummary(): JsonResponse
    {
        try {
            $today = now()->format('Y-m-d');

            $summary = [
                'total' => DeliveryTask::whereDate('assigned_at', $today)->count(),
                'completed' => DeliveryTask::whereDate('assigned_at', $today)
                    ->where('status', 'delivered')->count(),
                'in_progress' => DeliveryTask::whereDate('assigned_at', $today)
                    ->whereIn('status', ['accepted', 'picked_up', 'on_delivery'])->count(),
                'failed' => DeliveryTask::whereDate('assigned_at', $today)
                    ->where('status', 'cancelled')->count(),
                'pending' => DeliveryTask::whereDate('assigned_at', $today)
                    ->where('status', 'assigned')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Today\'s delivery summary',
                'date' => $today,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve summary',
            ], 500);
        }
    }

    /**
     * Helper: Get average delivery time
     */
    private function getAverageDeliveryTime(string $startDate, string $endDate): ?string
    {
        $deliveries = DeliveryTask::whereBetween('assigned_at', [$startDate, $endDate])
            ->whereNotNull('delivered_at')
            ->get();

        if ($deliveries->isEmpty()) {
            return null;
        }

        $totalSeconds = 0;
        $count = 0;

        foreach ($deliveries as $delivery) {
            if ($delivery->delivered_at && $delivery->assigned_at) {
                $totalSeconds += $delivery->delivered_at->diffInSeconds($delivery->assigned_at);
                $count++;
            }
        }

        if ($count === 0) {
            return null;
        }

        $avgSeconds = (int)($totalSeconds / $count);
        $hours = (int)($avgSeconds / 3600);
        $minutes = (int)(($avgSeconds % 3600) / 60);

        return "{$hours}h {$minutes}m";
    }

    /**
     * Helper: Get deliveries grouped by waiter
     */
    private function getDeliveriesByWaiter(string $startDate, string $endDate): array
    {
        return DeliveryTask::whereBetween('assigned_at', [$startDate, $endDate])
            ->with('waiter')
            ->get()
            ->groupBy('waiter_id')
            ->map(function ($group) {
                $waiter = $group->first()->waiter;
                return [
                    'waiter_id' => $waiter->id,
                    'waiter_name' => $waiter->user->full_name ?? 'Unknown',
                    'total' => $group->count(),
                    'completed' => $group->where('status', 'delivered')->count(),
                    'failed' => $group->where('status', 'cancelled')->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Helper: Get deliveries grouped by floor
     */
    private function getDeliveriesByFloor(string $startDate, string $endDate): array
    {
        return DeliveryTask::whereBetween('assigned_at', [$startDate, $endDate])
            ->with('floor')
            ->get()
            ->groupBy('floor_id')
            ->map(function ($group) {
                $floor = $group->first()->floor;
                return [
                    'floor_id' => $floor->id,
                    'floor_name' => $floor->name,
                    'total' => $group->count(),
                    'completed' => $group->where('status', 'delivered')->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Helper: Get deliveries grouped by status
     */
    private function getDeliveriesByStatus(string $startDate, string $endDate): array
    {
        return DeliveryTask::whereBetween('assigned_at', [$startDate, $endDate])
            ->get()
            ->groupBy('status')
            ->map(function ($group, $status) {
                return [
                    'status' => $status,
                    'count' => $group->count(),
                ];
            })
            ->values()
            ->toArray();
    }
}
