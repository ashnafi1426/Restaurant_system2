<?php

namespace App\Services\Waiter;

use App\Models\HotelFloor;
use App\Models\HotelShift;
use App\Models\Order;
use App\Models\DeliveryTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomaticWaiterAssignmentService
{
    public function __construct(
        private FloorResolverService $floorResolver,
        private ShiftResolverService $shiftResolver,
        private AssignmentStrategy $assignmentStrategy,
        private DeliveryWorkloadService $workloadService,
        private DeliveryNotificationService $notificationService
    ) {}

    public function assignWaiterToReadyOrder(Order $order): array
    {
        try {
            Log::info('Automatic Waiter Assignment Started', [
                'order_id' => $order->id,
            ]);

            $order->loadMissing(['reservation', 'room', 'guest', 'orderItems.menuItem']);

            $existing = DeliveryTask::where('order_id', $order->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existing) {
                return $this->successResponse($existing, 'Delivery already assigned');
            }

            return DB::transaction(function () use ($order) {
                $floor = $this->floorResolver->resolveForRoom($order->room);
                if (!$floor) {
                    $floor = $this->resolveFallbackFloor();
                    if (!$floor) {
                        $task = $this->workloadService->createWaitingDelivery($order, null, 'Floor could not be resolved');
                        return $this->waitingResponse($task, 'Floor could not be resolved');
                    }
                }

                $shift = $this->shiftResolver->getCurrentShift();
                if (!$shift) {
                    $shift = $this->resolveFallbackShift();
                    if (!$shift) {
                        $task = $this->workloadService->createWaitingDelivery($order, $floor, 'No active shift found');
                        return $this->waitingResponse($task, 'No active shift found');
                    }
                }

                // CRITICAL: Find best waiter within transaction to ensure fresh data
                $waiter = $this->assignmentStrategy->findBestWaiter($floor, $shift);
                if (!$waiter) {
                    $task = $this->workloadService->createWaitingDelivery($order, $floor, 'No available waiter');
                    return $this->waitingResponse($task, 'No available waiter');
                }

                // Assign delivery and increment waiter's current_orders atomically
                $task = $this->workloadService->assignDelivery($order, $waiter, $floor);
                $this->notificationService->notifyAssignment($task, $waiter);

                Log::info('✅ Order assigned successfully with load balancing', [
                    'order_id' => $order->id,
                    'waiter_id' => $waiter->id,
                    'waiter_name' => $waiter->user->name ?? 'Unknown',
                    'waiter_orders_before' => $waiter->current_orders - 1,
                    'waiter_orders_after' => $waiter->current_orders,
                    'floor' => $floor->floor_number,
                    'timestamp' => now(),
                ]);

                return $this->successResponse($task, 'Delivery successfully assigned');
            });

        } catch (Throwable $e) {
            Log::error('Automatic Assignment Exception', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse("Assignment failed: {$e->getMessage()}");
        }
    }

    private function resolveFallbackFloor(): ?HotelFloor
    {
        return HotelFloor::active()
            ->orderBy('floor_number')
            ->first();
    }

    private function resolveFallbackShift(): ?HotelShift
    {
        return HotelShift::active()
            ->orderBy('start_time')
            ->first();
    }

    private function successResponse(DeliveryTask $task, string $message): array
    {
        return [
            'success' => true,
            'delivery_task' => $task,
            'message' => $message,
            'status' => $task->status,
        ];
    }

    private function waitingResponse(DeliveryTask $task, string $message): array
    {
        return [
            'success' => false,
            'delivery_task' => $task,
            'message' => "Delivery waiting for manual assignment: {$message}",
            'status' => 'waiting_assignment',
        ];
    }

    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'status' => 'failed',
        ];
    }

    /**
     * Get delivery metrics for a given date or all time
     * If no date provided or date is today, returns all deliveries
     */
    public function getDeliveryMetrics(?\DateTime $date = null): array
    {
        // Query all deliveries (not just today's) to show complete historical data
        $deliveries = DeliveryTask::all();

        $total_deliveries = $deliveries->count();
        $completed = $deliveries->where('status', 'delivered')->count();
        $in_progress = $deliveries->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])->count();
        $failed = $deliveries->where('status', 'cancelled')->count();
        $pending = $deliveries->where('status', 'waiting_assignment')->count();

        $completedDeliveries = $deliveries->where('status', 'delivered');
        $avgDeliveryTime = $completedDeliveries->count() > 0
            ? round($completedDeliveries->average(function ($task) {
                return $task->assigned_at && $task->delivered_at
                    ? $task->assigned_at->diffInMinutes($task->delivered_at)
                    : 0;
            }), 2)
            : 0;

        $dateStr = $date ? $date->format('Y-m-d') : \Carbon\Carbon::today()->format('Y-m-d');

        return [
            'total_deliveries' => $total_deliveries,
            'completed' => $completed,
            'in_progress' => $in_progress,
            'failed' => $failed,
            'pending' => $pending,
            'average_delivery_time' => $avgDeliveryTime,
            'date' => $dateStr,
        ];
    }
}
