<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderCancelled Event
 * 
 * Broadcast when an order is cancelled by customer or staff.
 * Notifies both customer and kitchen staff in real-time.
 */
class OrderCancelled implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $orderId;
    public string $hotelId;
    public string $orderNumber;
    public ?string $cancelledBy;
    public ?string $reason;
    public string $cancelledAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Order $order,
        ?string $cancelledBy = null,
        ?string $reason = null
    ) {
        $this->orderId = $order->id;
        $this->hotelId = $order->hotel_id;
        $this->orderNumber = $order->order_number;
        $this->cancelledBy = $cancelledBy;
        $this->reason = $reason ?? 'Order cancelled';
        $this->cancelledAt = $order->cancelled_at?->toISOString() ?? now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Customer's order channel
            new PrivateChannel("orders.{$this->hotelId}.{$this->orderId}"),
            
            // Kitchen dashboard channel
            new PrivateChannel("hotel.{$this->hotelId}.kitchen"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'OrderCancelled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'hotel_id' => $this->hotelId,
            'order_number' => $this->orderNumber,
            'cancelled_by' => $this->cancelledBy,
            'reason' => $this->reason,
            'cancelled_at' => $this->cancelledAt,
        ];
    }
}
