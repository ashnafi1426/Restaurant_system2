<?php

namespace App\Notifications;

use App\Models\DeliveryTask;
use App\Models\Waiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ManagerAssignmentNotification
 * 
 * Notification sent to manager when delivery is assigned
 * For important assignments or when no waiter was available
 */
class ManagerAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public DeliveryTask $delivery;
    public ?Waiter $waiter;
    public string $assignmentStatus; // 'assigned' or 'waiting_assignment'
    public string $assignmentType; // 'automatic' or 'manual' or 'unassigned'

    /**
     * Create a new notification instance.
     *
     * @param DeliveryTask $delivery
     * @param Waiter|null $waiter
     * @param string $assignmentStatus
     * @param string $assignmentType
     */
    public function __construct(
        DeliveryTask $delivery,
        ?Waiter $waiter = null,
        string $assignmentStatus = 'assigned',
        string $assignmentType = 'automatic'
    ) {
        $this->delivery = $delivery;
        $this->waiter = $waiter;
        $this->assignmentStatus = $assignmentStatus;
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
        $message = (new MailMessage)
            ->subject('Delivery Status - Order #' . $this->delivery->order_id);

        if ($this->assignmentStatus === 'assigned') {
            $message->line('Delivery has been assigned to a waiter.')
                ->line('Order ID: ' . $this->delivery->order_id)
                ->line('Room: ' . $this->delivery->room_number)
                ->line('Assigned to: ' . ($this->waiter?->user?->name ?? 'Unknown'))
                ->line('Assignment Type: ' . $this->assignmentType);
        } else {
            $message->line('ATTENTION: No waiter is currently available for this delivery.')
                ->line('Order ID: ' . $this->delivery->order_id)
                ->line('Room: ' . $this->delivery->room_number)
                ->line('This delivery is waiting for manual assignment.')
                ->action('Assign Manually', url('/manager/delivery-management'));
        }

        return $message->action('View Delivery', url('/manager/delivery-management'))
            ->line('Please review if action is needed.');
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
            'waiter_id' => $this->waiter?->id,
            'waiter_name' => $this->waiter?->user?->name ?? 'Unassigned',
            'assignment_status' => $this->assignmentStatus,
            'assignment_type' => $this->assignmentType,
            'message' => $this->getMessageText(),
            'action_url' => '/manager/delivery-management',
            'timestamp' => now()->toIso8601String(),
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
            'type' => 'assignment_status',
            'title' => $this->assignmentStatus === 'assigned' ? 'Delivery Assigned' : 'No Waiter Available',
            'message' => $this->getMessageText(),
            'delivery_id' => $this->delivery->id,
            'order_id' => $this->delivery->order_id,
            'room_number' => $this->delivery->room_number,
            'waiter_id' => $this->waiter?->id,
            'waiter_name' => $this->waiter?->user?->name ?? 'Unassigned',
            'assignment_status' => $this->assignmentStatus,
            'assignment_type' => $this->assignmentType,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get appropriate message text
     *
     * @return string
     */
    private function getMessageText(): string
    {
        if ($this->assignmentStatus === 'assigned') {
            return "Order for room {$this->delivery->room_number} assigned to {$this->waiter?->user?->name ?? 'Unknown'} ({$this->assignmentType})";
        }

        return "Order for room {$this->delivery->room_number} - No waiter available. Manual assignment required.";
    }
}
