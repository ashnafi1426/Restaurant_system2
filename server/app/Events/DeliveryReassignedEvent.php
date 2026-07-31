<?php

namespace App\Events;

use App\Models\DeliveryTask;
use App\Models\Waiter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DeliveryReassignedEvent
 * 
 * Fired when a delivery is reassigned from one waiter to another.
 * 
 * STEP 16: Manager Override - Reassignment
 * 
 * Can be triggered by:
 * 1. Manager manually reassigning a delivery
 * 2. System auto-reassigning after waiter rejection
 * 
 * Triggers:
 * - Notify old waiter (delivery reassigned)
 * - Notify new waiter (new delivery assigned)
 * - Update real-time delivery board
 * - Log assignment change
 */
class DeliveryReassignedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public DeliveryTask $delivery,
        public ?Waiter $oldWaiter,
        public Waiter $newWaiter,
        public string $reason = 'No reason provided'
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('delivery.' . $this->delivery->id),
        ];
    }
}
