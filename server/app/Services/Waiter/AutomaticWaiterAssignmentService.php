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
        private WaiterSelectionEngine $selectionEngine,
        private DeliveryWorkloadService $workloadService,
        private DeliveryNotificationService $notificationService
    ) {}

    public function assignWaiterToReadyOrder(Order $order): array
    {
        try {
            Log::info('Automatic Waiter Assignment Started', [
                'order_id' => $order->id,
                'order_type' => $order->order_type,
            ]);

            $order->loadMissing(['reservation', 'room', 'table', 'guest', 'orderItems.menuItem']);

            $existing = DeliveryTask::where('order_id', $order->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existing) {
                if ($existing->waiter_id) {
                    $waiter = \App\Models\Waiter::find($existing->waiter_id);
                    if ($waiter && $waiter->user) {
                        try {
                            app(WaiterNotificationService::class)->notifyOrderReady($waiter->user, $existing);
                        } catch (\Throwable $e) {
                            Log::warning("Could not notify waiter of ready order: {$e->getMessage()}");
                        }
                    }
                }
                return $this->successResponse($existing, 'Delivery already assigned and waiter notified');
            }

            return DB::transaction(function () use ($order) {
                $isTableOrder = in_array($order->order_type, [Order::TYPE_DINE_IN, Order::TYPE_WALK_IN, 'dine_in', 'walk_in']) || !empty($order->table_id);
                
                if ($isTableOrder && $order->table_id) {
                    return $this->assignWalkInOrder($order);
                } else {
                    return $this->assignRoomServiceOrder($order);
                }
            });

        } catch (Throwable $e) {
            Log::error('Automatic Assignment Exception', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse("Assignment failed: {$e->getMessage()}");
        }
    }

    private function assignRoomServiceOrder(Order $order): array
    {
        $floor = $this->floorResolver->resolveForRoom($order->room);
        if (!$floor) {
            Log::warning('[ASSIGNMENT SERVICE] Room has no valid floor assigned', [
                'order_id' => $order->id,
                'room_id' => $order->room_id,
            ]);
            $task = $this->workloadService->createWaitingDelivery($order, null, 'Floor could not be resolved for room');
            return $this->waitingResponse($task, 'Floor could not be resolved for room');
        }

        $shift = $this->shiftResolver->getCurrentShift();
        if (!$shift) {
            $shift = $this->resolveFallbackShift();
        }

        $waiter = $this->selectionEngine->selectBestWaiter($floor, $shift);

        if (!$waiter) {
            Log::warning('[ASSIGNMENT SERVICE] No active waiter assigned to this floor', [
                'order_id' => $order->id,
                'floor_id' => $floor->id,
                'floor_name' => $floor->name ?? 'Floor ' . ($floor->floor_number ?? ''),
            ]);
            $task = $this->workloadService->createWaitingDelivery($order, $floor, 'No available waiter on assigned floor');
            return $this->waitingResponse($task, 'No available waiter on assigned floor');
        }

        $task = $this->workloadService->assignDelivery($order, $waiter, $floor);

        $this->notificationService->notifyAssignment($task, $waiter);

        Log::info(' [ASSIGNMENT SERVICE] Room service order assigned successfully', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => 'room_service',
            'waiter_id' => $waiter->id,
            'waiter_email' => $waiter->user->email ?? 'Unknown',
            'room_number' => $order->room->room_number ?? 'N/A',
            'floor' => $floor->floor_number,
            'floor_name' => $floor->name,
        ]);

        return $this->successResponse($task, 'Room service delivery successfully assigned');
    }

    private function assignWalkInOrder(Order $order): array
    {
        $table = $order->table;
        
        if (!$table) {
            $task = $this->workloadService->createWaitingDelivery($order, null, 'Table not found');
            return $this->waitingResponse($task, 'Table not found');
        }

        $shift = $this->shiftResolver->getCurrentShift();
        if (!$shift) {
            $shift = $this->resolveFallbackShift();
            if (!$shift) {
                $task = $this->workloadService->createWaitingDelivery($order, null, 'No active shift found');
                return $this->waitingResponse($task, 'No active shift found');
            }
        }

        $waiter = $this->selectionEngine->selectWaiterForTable($table, $shift);

        if (!$waiter) {
            $task = $this->workloadService->createWaitingDelivery($order, null, 'No waiter assigned to table');
            return $this->waitingResponse($task, 'No waiter assigned to table');
        }

        $task = $this->workloadService->assignTableDelivery($order, $waiter, $table);

        $this->notificationService->notifyAssignment($task, $waiter);

        Log::info(' [ASSIGNMENT SERVICE] Walk-in order assigned successfully', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => 'walk_in',
            'waiter_id' => $waiter->id,
            'waiter_email' => $waiter->user->email ?? 'Unknown',
            'table_number' => $table->table_number,
            'table_section' => $table->section ?? 'N/A',
        ]);

        return $this->successResponse($task, 'Walk-in order successfully assigned');
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

    public function getDeliveryMetrics(?\DateTime $date = null): array
    {
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
