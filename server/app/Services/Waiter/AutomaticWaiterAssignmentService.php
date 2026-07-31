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
            Log::info('🔵 Automatic Waiter Assignment Workflow Started', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            // Ensure order is loaded
            $order->loadMissing(['reservation', 'room', 'guest', 'orderItems.menuItem']);

            // Prevent duplicate assignments
            $existing = DeliveryTask::where('order_id', $order->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existing) {
                Log::warning('Delivery Task Already Exists', [
                    'order_id' => $order->id,
                    'delivery_id' => $existing->id,
                    'existing_status' => $existing->status,
                ]);
                return $this->successResponse($existing, 'Delivery already assigned');
            }

            return DB::transaction(function () use ($order) {
                // 1. Resolve Floor
                $floor = $this->floorResolver->resolveForRoom($order->room);
                if (!$floor) {
                    $floor = $this->resolveFallbackFloor();

                    if ($floor) {
                        Log::warning('Falling back to active floor for waiter assignment', [
                            'order_id' => $order->id,
                            'fallback_floor_id' => $floor->id,
                        ]);
                    } else {
                        $task = $this->workloadService->createWaitingDelivery($order, null, 'Floor could not be resolved or inactive');
                        $this->notificationService->notifyManagerOfWaiting($task, 'Floor could not be resolved or inactive');
                        return $this->waitingResponse($task, 'Floor could not be resolved or inactive');
                    }
                }

                // 2. Resolve Shift
                $shift = $this->shiftResolver->getCurrentShift();
                if (!$shift) {
                    $shift = $this->resolveFallbackShift();

                    if ($shift) {
                        Log::warning('Falling back to active shift for waiter assignment', [
                            'order_id' => $order->id,
                            'fallback_shift_id' => $shift->id,
                        ]);
                    } else {
                        $task = $this->workloadService->createWaitingDelivery($order, $floor, 'No active shift found');
                        $this->notificationService->notifyManagerOfWaiting($task, 'No active shift found');
                        return $this->waitingResponse($task, 'No active shift found');
                    }
                }

                // 3. Find Best Waiter
                $waiter = $this->assignmentStrategy->findBestWaiter($floor, $shift);
                if (!$waiter) {
                    $task = $this->workloadService->createWaitingDelivery($order, $floor, 'No available waiter');
                    $this->notificationService->notifyManagerOfWaiting($task, 'No available waiter');
                    return $this->waitingResponse($task, 'No available waiter');
                }

                // 4. Assign Delivery with status='accepted'
                // Automatic assignments are immediately accepted by the system
                // (The algorithm has verified the waiter is best fit and available)
                // Task will immediately appear on waiter's "Ready for Pickup" page
                $task = $this->workloadService->assignDelivery($order, $waiter, $floor);

                // 5. Notify Waiter
                $this->notificationService->notifyAssignment($task, $waiter);

                Log::info('✅ Automatic Assignment Complete', [
                    'order_id' => $order->id,
                    'waiter_id' => $waiter->id,
                    'delivery_task_id' => $task->id,
                    'delivery_status' => $task->status,
                ]);

                return $this->successResponse($task, 'Delivery successfully assigned');
            });

        } catch (Throwable $e) {
            Log::error('❌ Automatic Assignment Workflow Exception', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
}