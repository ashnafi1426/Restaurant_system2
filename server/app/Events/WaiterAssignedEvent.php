<?php

namespace App\Events;

use App\Models\Waiter;
use App\Models\DeliveryTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * WaiterAssignedEvent
 * 
 * Fired when a delivery is assigned to a waiter
 * Can be automatic (from OrderReadyEvent) or manual (from manager)
 * Triggers notifications and workload updates
 */
class WaiterAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeliveryTask $delivery;
    public Waiter $waiter;
    public string $deliveryId;
    public string $waiterId;
    public string $waiterName;
    public string $assignmentType; // 'automatic' or 'manual'
    public string $orderId;
    public string $roomNumber;
    public int $floorNumber;
    public string $timestamp;

    /**
     * Create a new event instance.
     *
     * @param DeliveryTask $delivery
     * @param Waiter $waiter
     * @param string $assignmentType
     * @return void
     */
    public function __construct(DeliveryTask $delivery, Waiter $waiter, string $assignmentType = 'automatic')
    {
        $this->delivery = $delivery;
        $this->waiter = $waiter;
        $this->deliveryId = $delivery->id;
        $this->waiterId = $waiter->id;
        $this->waiterName = $waiter->user->name ?? $waiter->name ?? 'Unknown';
        $this->assignmentType = $assignmentType;
        $this->orderId = $delivery->order_id;

        // Resolve the real room/floor from the delivery relations when available.
        $this->roomNumber = $delivery->room?->room_number
            ?? $delivery->order?->room?->room_number
            ?? 'Unknown';

        $this->floorNumber = $delivery->floor?->floor_number
            ?? $delivery->room?->floor?->floor_number
            ?? $delivery->order?->room?->floor?->floor_number
            ?? 1;
        
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('waiter.' . $this->waiterId),
            new PrivateChannel('manager'),
            new PrivateChannel('delivery.' . $this->deliveryId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'waiter.assigned';
    }
}
