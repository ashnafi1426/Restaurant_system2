<?php

namespace App\Notifications;

use App\Models\DeliveryTask;
use App\Models\Waiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DeliveryReassignedNotification
 * 
 * Notification sent to waiter when delivery is reassigned away from them
 * Includes reason for reassignment
 */
class DeliveryReassignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public DeliveryTask $delivery;
    public Waiter $newWaiter;
    public string $reason;

    /**
     * Create a new notification instance.
     *
     * @param DeliveryTask $delivery
     * @param Waiter $newWaiter
     * @param string $reason
     */
    public function __construct(DeliveryTask $delivery, Waiter $newWaiter, string $reason = 'Reassigned by manager')
    {
        $this->delivery = $delivery;
        $this->newWaiter = $newWaiter;
        $this->reason = $reason;
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
            ->subject('Delivery Reassigned - Order #' . $this->delivery->order_id)
            ->line('A delivery has been reassigned to another waiter.')
            ->line('Order ID: ' . $this->delivery->order_id)
            ->line('Room: ' . $this->delivery->room_number)
            ->line('Reason: ' . $this->reason)
            ->line('New Waiter: ' . ($this->newWaiter->user->name ?? 'Unknown'))
            ->action('View Dashboard', url('/waiter'))
            ->line('Thank you for your understanding.');
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
            'new_waiter_id' => $this->newWaiter->id,
            'new_waiter_name' => $this->newWaiter->user->name ?? 'Unknown',
            'reason' => $this->reason,
            'reassigned_at' => now()->toIso8601String(),
            'message' => "Delivery for room {$this->delivery->room_number} has been reassigned to {$this->newWaiter->user->name ?? 'Unknown'}",
            'action_url' => '/waiter',
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
            'type' => 'delivery_reassigned',
            'title' => 'Delivery Reassigned',
            'message' => "Your delivery for room {$this->delivery->room_number} has been reassigned",
            'delivery_id' => $this->delivery->id,
            'order_id' => $this->delivery->order_id,
            'room_number' => $this->delivery->room_number,
            'new_waiter_id' => $this->newWaiter->id,
            'new_waiter_name' => $this->newWaiter->user->name ?? 'Unknown',
            'reason' => $this->reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
