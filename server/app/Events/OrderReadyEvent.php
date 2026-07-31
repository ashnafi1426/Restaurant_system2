<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderReadyEvent
 * 
 * Fired when kitchen marks an order as ready for delivery.
 * Triggers automatic waiter assignment workflow.
 * 
 * Phase 4: AUTOMATIC WAITER ASSIGNMENT TRIGGER
 * 
 * Flow:
 * 1. Kitchen marks order as READY
 * 2. KitchenService::markReady() dispatches this event
 * 3. AssignWaiterListener listens to this event
 * 4. Listener calls AutomaticWaiterAssignmentService
 * 5. Service automatically assigns delivery task to best available waiter
 */
class OrderReadyEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @var Order The order that became ready
     */
    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
