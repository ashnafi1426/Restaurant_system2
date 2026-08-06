<?php

namespace App\Listeners;

use App\Events\WaiterAssignedEvent;
use App\Events\DeliveryReassignedEvent;
use App\Models\WaiterNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Exception;
class NotifyWaiterListener implements ShouldQueue
{
    use InteractsWithQueue;
    public $tries = 3;
    public function __construct() {}

    public function handleWaiterAssigned(WaiterAssignedEvent $event): void
    {
        try {
            Log::info('NotifyWaiterListener: Creating waiter notification', [
                'waiter_id' => $event->waiterId,
                'delivery_id' => $event->deliveryId,
                'assignment_type' => $event->assignmentType,
            ]);

            // Create notification for waiter
            $notification = WaiterNotification::create([
                'waiter_id' => $event->waiterId,
                'delivery_id' => $event->deliveryId,
                'type' => 'delivery_assigned',
                'title' => 'New Delivery Assigned',
                'message' => "Order for room {$event->roomNumber} has been assigned to you",
                'data' => [
                    'delivery_id' => $event->deliveryId,
                    'order_id' => $event->orderId,
                    'room_number' => $event->roomNumber,
                    'floor' => $event->floorNumber,
                    'assignment_type' => $event->assignmentType,
                    'timestamp' => $event->timestamp,
                ],
                'read_at' => null,
            ]);

            // Broadcast notification via WebSocket
            \Illuminate\Support\Facades\Broadcast::channel("waiter.{$event->waiterId}")
                ->send([
                    'type' => 'delivery_assigned',
                    'notification_id' => $notification->id,
                    'data' => $notification->data,
                ]);

            Log::info('Waiter notification created and broadcast', [
                'notification_id' => $notification->id,
                'waiter_id' => $event->waiterId,
            ]);

        } catch (Exception $e) {
            Log::error('NotifyWaiterListener: Error creating waiter notification', [
                'waiter_id' => $event->waiterId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle DeliveryReassignedEvent
     *
     * @param DeliveryReassignedEvent $event
     * @return void
     */
    public function handleDeliveryReassigned(DeliveryReassignedEvent $event): void
    {
        try {
            Log::info('NotifyWaiterListener: Creating reassignment notifications', [
                'new_waiter_id' => $event->newWaiterId,
                'previous_waiter_id' => $event->previousWaiterId,
                'delivery_id' => $event->deliveryId,
            ]);

            // Notify new waiter
            $newNotification = WaiterNotification::create([
                'waiter_id' => $event->newWaiterId,
                'delivery_id' => $event->deliveryId,
                'type' => 'delivery_assigned',
                'title' => 'New Delivery Assigned',
                'message' => "Order for room {$event->roomNumber} has been assigned to you (reassigned from {$event->previousWaiterName})",
                'data' => [
                    'delivery_id' => $event->deliveryId,
                    'order_id' => $event->orderId,
                    'room_number' => $event->roomNumber,
                    'reason' => $event->reason,
                    'timestamp' => $event->timestamp,
                ],
                'read_at' => null,
            ]);

            // Notify previous waiter that delivery was removed
            if ($event->previousWaiterId) {
                $previousNotification = WaiterNotification::create([
                    'waiter_id' => $event->previousWaiterId,
                    'delivery_id' => $event->deliveryId,
                    'type' => 'delivery_removed',
                    'title' => 'Delivery Reassigned',
                    'message' => "Your delivery for room {$event->roomNumber} has been reassigned to {$event->newWaiterName}. Reason: {$event->reason}",
                    'data' => [
                        'delivery_id' => $event->deliveryId,
                        'new_waiter_id' => $event->newWaiterId,
                        'reason' => $event->reason,
                        'timestamp' => $event->timestamp,
                    ],
                    'read_at' => null,
                ]);
            }

            // Broadcast notifications
            \Illuminate\Support\Facades\Broadcast::channel("waiter.{$event->newWaiterId}")
                ->send([
                    'type' => 'delivery_reassigned',
                    'notification_id' => $newNotification->id,
                    'data' => $newNotification->data,
                ]);

            if ($event->previousWaiterId) {
                \Illuminate\Support\Facades\Broadcast::channel("waiter.{$event->previousWaiterId}")
                    ->send([
                        'type' => 'delivery_removed',
                        'notification_id' => $previousNotification->id,
                        'data' => $previousNotification->data,
                    ]);
            }

            Log::info('Reassignment notifications created and broadcast', [
                'new_waiter_id' => $event->newWaiterId,
                'previous_waiter_id' => $event->previousWaiterId,
            ]);

        } catch (Exception $e) {
            Log::error('NotifyWaiterListener: Error creating reassignment notifications', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
