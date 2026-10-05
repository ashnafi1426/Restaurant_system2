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
 * OrderCreated Event
 * 
 * Broadcast when a new order is created by a customer.
 * Notifies kitchen staff in real-time so they can start preparing immediately.
 * 
 * Broadcasts to hotel-level channels (not to customer, as they're redirected to OrderStatusPage).
 */
class OrderCreated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $orderId;
    public string $hotelId;
    public string $orderNumber;
    public string $orderType;
    public string $status;
    public ?string $roomNumber;
    public ?string $tableNumber;
    public ?string $customerName;
    public int $itemsCount;
    public float $total;
    public string $orderTime;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        // Load relationships needed for broadcast
        $order->load(['orderItems', 'room', 'table', 'guest']);
        
        $this->orderId = $order->id;
        $this->hotelId = $order->hotel_id;
        $this->orderNumber = $order->order_number;
        $this->orderType = $order->order_type ?? 'room_service';
        $this->status = $order->status ?? 'pending';
        $this->roomNumber = $order->room?->room_number;
        $this->tableNumber = $order->table?->table_number;
        $this->customerName = $order->guest?->name ?? $order->guest?->first_name . ' ' . $order->guest?->last_name ?? 'Guest';
        $this->itemsCount = $order->orderItems->count();
        $this->total = (float) $order->total;
        $this->orderTime = $order->order_time?->toISOString() ?? now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Kitchen dashboard channel
            new PrivateChannel("hotel.{$this->hotelId}.kitchen"),
            
            // Hotel-wide orders channel for dashboards
            new PrivateChannel("hotel.{$this->hotelId}.orders"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'OrderCreated';
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
            'order_type' => $this->orderType,
            'status' => $this->status,
            'room_number' => $this->roomNumber,
            'table_number' => $this->tableNumber,
            'customer_name' => $this->customerName,
            'items_count' => $this->itemsCount,
            'total' => $this->total,
            'order_time' => $this->orderTime,
            'created_at' => now()->toISOString(),
        ];
    }
}
