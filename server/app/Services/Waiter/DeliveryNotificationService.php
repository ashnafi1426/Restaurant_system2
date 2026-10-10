<?php

namespace App\Services\Waiter;

use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Models\Notification;
use App\Models\User;
use App\Events\WaiterAssignedEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliveryNotificationService
{
    public function notifyAssignment(DeliveryTask $task, Waiter $waiter): void
    {
        try {
            $order = $task->order;

            WaiterAssignedEvent::dispatch($task, $waiter, $task->assignment_type ?? 'automatic');

            Log::info(' [NOTIFICATION SERVICE] WaiterAssignedEvent dispatched', [
                'waiter_id'   => $waiter->id,
                'waiter_name' => $waiter->user->name ?? 'Unknown',
                'user_id'     => $waiter->user_id,
                'delivery_id' => $task->id,
                'order_id'    => $order->id,
                'order_number' => $order->order_number,
                'room_number' => $order->room->room_number ?? 'N/A',
                'assignment_type' => $task->assignment_type,
            ]);

        } catch (Throwable $e) {
            Log::error(' [NOTIFICATION SERVICE] Event dispatch exception', [
                'waiter_id'   => $waiter->id,
                'delivery_id' => $task->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
        }
    }

    public function notifyManagerOfWaiting(DeliveryTask $task, string $reason): void
    {
        try {
            $managers = User::whereIn('role', ['manager', 'admin', 'administrator'])->get();

            foreach ($managers as $manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'type'    => 'general',
                    'title'   => 'Delivery Waiting Manual Assignment',
                    'message' => "Order #{$task->order->order_number} needs manual assignment. Reason: {$reason}",
                    'read'    => false,
                ]);
            }

            Log::info('Managers Notified of Waiting Delivery', [
                'delivery_id' => $task->id,
                'reason'      => $reason,
            ]);
        } catch (Throwable $e) {
            Log::error('Manager Notification Exception', [
                'delivery_id' => $task->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}

