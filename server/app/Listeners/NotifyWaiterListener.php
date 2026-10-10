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
            $delivery = $event->delivery;
            $order = $delivery->order;
            
            $itemsList = $order->orderItems
                ->map(fn ($item) => "{$item->quantity}x {$item->menuItem->name}")
                ->implode(', ');
            
            $roomNumber = $order->room->room_number ?? 'Unknown';
            $guestName = ($order->guest->first_name ?? '') . ' ' . ($order->guest->last_name ?? '');
            
            Log::info('📨 [NOTIFY LISTENER] Creating waiter notification', [
                'waiter_id' => $event->waiterId,
                'waiter_name' => $event->waiterName,
                'delivery_id' => $event->deliveryId,
                'order_number' => $order->order_number,
                'room_number' => $roomNumber,
                'assignment_type' => $event->assignmentType,
            ]);

            $waiter = $event->waiter ?? \App\Models\Waiter::with('user')->find($event->waiterId);
            $userId = $waiter?->user_id ?? $order->user_id ?? null;
            $hotelId = $order->hotel_id ?? $waiter?->hotel_id;

            $notification = WaiterNotification::create([
                'hotel_id' => $hotelId,
                'user_id' => $userId,
                'waiter_id' => $event->waiterId,
                'delivery_task_id' => $event->deliveryId,
                'type' => 'delivery_assigned',
                'title' => "New Delivery: Order #{$order->order_number}",
                'message' => "Room {$roomNumber} — {$guestName}\nItems: {$itemsList}\n\nReady for pickup!",
                'data' => [
                    'delivery_id' => $event->deliveryId,
                    'order_id' => $event->orderId,
                    'order_number' => $order->order_number,
                    'room_number' => $roomNumber,
                    'floor' => $event->floorNumber,
                    'guest_name' => $guestName,
                    'items' => $itemsList,
                    'assignment_type' => $event->assignmentType,
                    'timestamp' => $event->timestamp,
                ],
                'read' => false,
                'is_read' => false,
                'created_at' => now(),
            ]);

            Log::info(' [NOTIFY LISTENER] Waiter notification created', [
                'notification_id' => $notification->id,
                'waiter_id' => $event->waiterId,
                'delivery_id' => $event->deliveryId,
                'message_preview' => substr($notification->message, 0, 50) . '...',
            ]);

        } catch (Exception $e) {
            Log::error(' [NOTIFY LISTENER] Error creating waiter notification', [
                'waiter_id' => $event->waiterId,
                'delivery_id' => $event->deliveryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function handleDeliveryReassigned(DeliveryReassignedEvent $event): void
    {
        try {
            Log::info('NotifyWaiterListener: Creating reassignment notifications', [
                'new_waiter_id' => $event->newWaiterId,
                'previous_waiter_id' => $event->previousWaiterId,
                'delivery_id' => $event->deliveryId,
            ]);

            $newWaiter = \App\Models\Waiter::find($event->newWaiterId);
            $newUserId = $newWaiter?->user_id;
            $hotelId = $newWaiter?->hotel_id;

            $newNotification = WaiterNotification::create([
                'hotel_id' => $hotelId,
                'user_id' => $newUserId,
                'waiter_id' => $event->newWaiterId,
                'delivery_task_id' => $event->deliveryId,
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
                'read' => false,
                'is_read' => false,
                'read_at' => null,
            ]);

            if ($event->previousWaiterId) {
                $prevWaiter = \App\Models\Waiter::find($event->previousWaiterId);
                $prevUserId = $prevWaiter?->user_id;

                $previousNotification = WaiterNotification::create([
                    'hotel_id' => $hotelId ?? $prevWaiter?->hotel_id,
                    'user_id' => $prevUserId,
                    'waiter_id' => $event->previousWaiterId,
                    'delivery_task_id' => $event->deliveryId,
                    'type' => 'delivery_removed',
                    'title' => 'Delivery Reassigned',
                    'message' => "Your delivery for room {$event->roomNumber} has been reassigned to {$event->newWaiterName}. Reason: {$event->reason}",
                    'data' => [
                        'delivery_id' => $event->deliveryId,
                        'new_waiter_id' => $event->newWaiterId,
                        'reason' => $event->reason,
                        'timestamp' => $event->timestamp,
                    ],
                    'read' => false,
                    'is_read' => false,
                    'read_at' => null,
                ]);
            }

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
