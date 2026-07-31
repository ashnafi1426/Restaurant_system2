<?php

namespace App\Services\Waiter;

use App\Models\WaiterNotification;
use App\Models\User;

class WaiterNotificationService
{
    public function send(User $user, string $type, string $title, string $message, ?array $data = null): WaiterNotification
    {
        return WaiterNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
    public function notifyOrderAssignment(User $waiter, $assignment)
    {
        return $this->send(
            $waiter,
            'order_assigned',
            'New Order Assigned',
            "Order #{$assignment->order->order_number} has been assigned to you.",
            [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'order_number' => $assignment->order->order_number,
            ]
        );
    }
    public function notifyOrderReady(User $waiter, $assignment)
    {
        return $this->send(
            $waiter,
            'order_ready',
            'Order Ready for Pickup',
            "Order #{$assignment->order->order_number} is ready for pickup.",
            [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'room_number' => $assignment->order->room_number,
            ]
        );
    }
    public function notifyDeliveryStarted(User $waiter, $assignment)
    {
        return $this->send(
            $waiter,
            'delivery_started',
            'Delivery Started',
            "You have started delivery for Order #{$assignment->order->order_number} to Room #{$assignment->order->room_number}.",
            [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
            ]
        );
    }
    public function notifyDeliveryCompleted(User $waiter, $assignment)
    {
        return $this->send(
            $waiter,
            'delivery_completed',
            'Delivery Completed',
            "Order #{$assignment->order->order_number} delivered successfully!",
            [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'delivery_time' => $assignment->delivery_time_minutes ?? 0,
            ]
        );
    }
    public function notifyDeliveryFailed(User $waiter, $assignment, $reason = null)
    {
        return $this->send(
            $waiter,
            'delivery_failed',
            'Delivery Failed',
            "Delivery for Order #{$assignment->order->order_number} failed. Reason: {$reason}",
            [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'reason' => $reason,
            ]
        );
    }
    public function notifyAssignmentRejected(User $waiter, $assignment, $reason = null)
    {
        return $this->send(
            $waiter,
            'assignment_rejected',
            'Assignment Rejected',
            "Your rejection of Order #{$assignment->order->order_number} has been recorded.",
            [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'reason' => $reason,
            ]
        );
    }
    public function notifyHighPriorityOrder(User $waiter, $assignment)
    {
        return $this->send(
            $waiter,
            'high_priority_order',
            'High Priority Order',
            "Urgent delivery needed for Order #{$assignment->order->order_number} to Room #{$assignment->order->room_number}.",
            [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'priority' => 'high',
            ]
        );
    }
    public function notifyShiftReminder(User $waiter, string $shift, \DateTime $startTime)
    {
        return $this->send(
            $waiter,
            'shift_reminder',
            'Shift Reminder',
            "Your {$shift} shift starts at " . $startTime->format('h:i A'),
            [
                'shift' => $shift,
                'start_time' => $startTime,
            ]
        );
    }
    public function notifyPerformanceMilestone(User $waiter, int $deliveries, float $rating)
    {
        return $this->send(
            $waiter,
            'performance_milestone',
            '🎉 Great Performance!',
            "You've completed {$deliveries} deliveries with an excellent rating of {$rating}/5!",
            [
                'deliveries' => $deliveries,
                'rating' => $rating,
            ]
        );
    }
    public function notifySystem(User $waiter, string $title, string $message, ?array $data = null)
    {
        return $this->send($waiter, 'system', $title, $message, $data);
    }
    public function getUnreadCount(User $user): int
    {
        return WaiterNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }
    public function markAsRead(WaiterNotification $notification): bool
    {
        return $notification->markAsRead();
    }
    public function markAllAsRead(User $user): void
    {
        WaiterNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
    public function deleteOldNotifications($days = 30): void
    {
        WaiterNotification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
