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
 * PaymentStatusUpdated Event
 * 
 * Broadcast when an order's payment status changes.
 * Triggered by Chapa webhook after successful payment processing.
 * 
 * Customer receives real-time payment confirmation without page refresh.
 */
class PaymentStatusUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $orderId;
    public string $hotelId;
    public string $orderNumber;
    public string $paymentStatus;
    public ?string $paymentMethod;
    public ?string $paymentType;
    public float $amount;
    public ?string $transactionRef;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Order $order,
        ?string $transactionRef = null,
        ?string $paymentMethod = null
    ) {
        $this->orderId = $order->id;
        $this->hotelId = $order->hotel_id;
        $this->orderNumber = $order->order_number;
        $this->paymentStatus = $this->normalizePaymentStatus($order);
        $this->paymentMethod = $paymentMethod ?? 'chapa';
        $this->paymentType = $order->payment_type;
        $this->amount = (float) $order->total;
        $this->transactionRef = $transactionRef;
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
            // Cashier and hotel channels
            new PrivateChannel("hotel.{$this->hotelId}.orders"),
            new PrivateChannel("payments.{$this->hotelId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'PaymentStatusUpdated';
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
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'payment_type' => $this->paymentType,
            'amount' => $this->amount,
            'transaction_ref' => $this->transactionRef,
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Normalize payment status from order data.
     */
    protected function normalizePaymentStatus(Order $order): string
    {
        // Check if order has payment_status field
        if (isset($order->payment_status)) {
            return $order->payment_status;
        }
        
        // Fallback: infer from payment_type
        if ($order->payment_type === 'room_charge') {
            return 'pending'; // Room charges are settled at checkout
        }
        
        // Default for completed orders
        if ($order->status === 'served' || $order->status === 'completed') {
            return 'paid';
        }
        
        return 'pending';
    }
}
