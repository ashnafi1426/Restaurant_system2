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
 * OrderStatusUpdated Event
 * 
 * Broadcast when an order's status changes (pending → preparing → ready → served).
 * 
 * Broadcasts to TWO channels:
 * 1. orders.{hotel_id}.{order_id} - Customer tracking their specific order
 * 2. hotel.{hotel_id}.kitchen - Kitchen dashboard for real-time order management
 * 
 * Implements ShouldQueue for async broadcasting (doesn't block HTTP response).
 */
class OrderStatusUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $orderId;
    public string $hotelId;
    public string $status;
    public ?string $previousStatus;
    public string $orderNumber;
    public ?string $roomNumber;
    public ?string $tableNumber;
    public ?string $chefId;
    public ?string $message;
    public ?int $estimatedCompletionMinutes;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?string $previousStatus = null)
    {
        $this->orderId = $order->id;
        $this->hotelId = $order->hotel_id;
        $this->status = $order->status;
        $this->previousStatus = $previousStatus;
        $this->orderNumber = $order->order_number;
        $this->roomNumber = $order->room?->room_number;
        $this->tableNumber = $order->table?->table_number;
        $this->chefId = $order->chef_id;
        $this->message = $this->generateStatusMessage($order->status);
        $this->estimatedCompletionMinutes = $this->estimateCompletionTime($order);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Private channel for customer tracking their specific order
            new PrivateChannel("orders.{$this->hotelId}.{$this->orderId}"),
            
            // Private channel for kitchen dashboard (hotel-wide)
            new PrivateChannel("hotel.{$this->hotelId}.kitchen"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'hotel_id' => $this->hotelId,
            'status' => $this->status,
            'previous_status' => $this->previousStatus,
            'order_number' => $this->orderNumber,
            'room_number' => $this->roomNumber,
            'table_number' => $this->tableNumber,
            'chef_id' => $this->chefId,
            'message' => $this->message,
            'estimated_completion_minutes' => $this->estimatedCompletionMinutes,
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate human-readable status message.
     */
    protected function generateStatusMessage(string $status): string
    {
        return match($status) {
            'pending' => 'Your order has been received and is waiting to be prepared.',
            'preparing' => 'Our chef is preparing your delicious meal.',
            'ready' => 'Your order is ready! Please proceed to pick it up or wait for delivery.',
            'served' => 'Your order has been completed. Enjoy your meal!',
            'cancelled' => 'This order has been cancelled.',
            default => 'Order status updated.',
        };
    }

    /**
     * Estimate completion time based on order status.
     */
    protected function estimateCompletionTime(Order $order): ?int
    {
        if ($order->status === 'pending') {
            // Estimate based on number of items (rough estimate: 5 min per item)
            $itemCount = $order->orderItems()->count();
            return min(max($itemCount * 5, 15), 45); // Between 15-45 minutes
        }
        
        if ($order->status === 'preparing') {
            // If already preparing, estimate time remaining
            return 20;
        }
        
        if ($order->status === 'ready') {
            return 0; // Order is ready now
        }
        
        return null;
    }
}
