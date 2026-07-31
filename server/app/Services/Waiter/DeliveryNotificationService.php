<?php

namespace App\Services\Waiter;

use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliveryNotificationService
{
    /**
     * Trigger standardized notification to waiter.
     * Uses type='delivery' which is a valid ENUM value in the notifications table.
     */
    public function notifyAssignment(DeliveryTask $task, Waiter $waiter): void
    {
        try {
            $order = $task->order;

            $itemsList = $order->orderItems
                ->map(fn ($item) => "{$item->quantity}x {$item->menuItem->name}")
                ->implode(', ');

            Notification::create([
                'user_id' => $waiter->user_id,
                'type'    => 'delivery',   // Valid ENUM: ('reservation','order','delivery','general')
                'title'   => "New Delivery: Order #{$order->order_number}",
                'message' => "Room {$order->room->room_number} — {$order->guest->first_name} {$order->guest->last_name}. Items: {$itemsList}",
                'read'    => false,
            ]);

            Log::info('Waiter Notified of Assignment', [
                'waiter_id'   => $waiter->id,
                'user_id'     => $waiter->user_id,
                'delivery_id' => $task->id,
            ]);
        } catch (Throwable $e) {
            Log::error('Waiter Notification Exception', [
                'waiter_id'   => $waiter->id,
                'delivery_id' => $task->id,
                'error'       => $e->getMessage(),
            ]);
            // Suppress — notification failure must never fail the delivery assignment transaction
        }
    }

    /**
     * Trigger standardized notification to managers when assignment fails.
     * Uses type='general' which is a valid ENUM value in the notifications table.
     */
    public function notifyManagerOfWaiting(DeliveryTask $task, string $reason): void
    {
        try {
            $managers = User::whereIn('role', ['manager', 'admin', 'administrator'])->get();

            foreach ($managers as $manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'type'    => 'general',  // Valid ENUM: ('reservation','order','delivery','general')
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
