<?php

namespace App\Notifications;

use App\Models\DeliveryTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

/**
 * DeliveryAssignedNotification
 * 
 * Notification sent to waiter when a delivery is assigned to them
 * Includes order details and urgent action required
 */
class DeliveryAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public DeliveryTask $delivery;
    public string $assignmentType; // 'automatic' or 'manual'

    /**
     * Create a new notification instance.
     *
     * @param DeliveryTask $delivery
     * @param string $assignmentType
     */
    public function __construct(DeliveryTask $delivery, string $assignmentType = 'automatic')
    {
        $this->delivery = $delivery;
        $this->assignmentType = $assignmentType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param object $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Delivery Assigned - Order #' . $this->delivery->order_id)
            ->line('A new delivery has been assigned to you.')
            ->line('Room: ' . $this->delivery->room_number)
            ->line('Order ID: ' . $this->delivery->order_id)
            ->action('View Delivery', url('/waiter/on-delivery'))
            ->line('Please proceed to the kitchen for pickup.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'order_id' => $this->delivery->order_id,
            'room_number' => $this->delivery->room_number,
            'floor' => $this->delivery->floor_id,
            'assignment_type' => $this->assignmentType,
            'assigned_at' => $this->delivery->assigned_at,
            'message' => "New delivery for room {$this->delivery->room_number} is ready for pickup.",
            'action_url' => '/waiter/on-delivery',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param object $notifiable
     * @return \Illuminate\Notifications\Messages\BroadcastMessage
     */
    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'notification_id' => $this->id ?? null,
            'type' => 'delivery_assigned',
            'title' => 'New Delivery Assigned',
            'message' => "Order for room {$this->delivery->room_number} is ready for delivery",
            'delivery_id' => $this->delivery->id,
            'order_id' => $this->delivery->order_id,
            'room_number' => $this->delivery->room_number,
            'floor' => $this->delivery->floor_id,
            'assignment_type' => $this->assignmentType,
            'assigned_at' => $this->delivery->assigned_at,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
