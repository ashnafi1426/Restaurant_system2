<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Services\RestaurantChargeService;
class KitchenService
{
    public function getKitchenOrders(): array
    {
        return [

            'pending' => $this->getOrdersByStatus(Order::STATUS_PENDING),

            'preparing' => $this->getOrdersByStatus(Order::STATUS_PREPARING),

            'ready' => $this->getOrdersByStatus(Order::STATUS_READY),

            'served' => $this->getOrdersByStatus(Order::STATUS_SERVED),

        ];
    }
    protected function getOrdersByStatus(string $status): Collection
    {
        return Order::query()

            ->with([
                'guest',
                'room',
                'reservation',
                'orderItems',
                'orderItems.menuItem',
            ])

            ->where('status', $status)

            ->latest('order_time')

            ->get();
    }

    /**
     * --------------------------------------------------------------------------
     * Load all relationships for a single order
     * --------------------------------------------------------------------------
     */
    protected function loadOrderRelations(Order $order): Order
    {
        return $order->load([

            'guest',

            'room',

            'reservation',

            'orderItems',

            'orderItems.menuItem',

        ]);
    }
    protected function validateStatusTransition(
        Order $order,
        string $expectedStatus
    ): void {

        if ($order->status !== $expectedStatus) {

            throw new \Exception(
                "Order must be '{$expectedStatus}' before this action."
            );

        }
    }
    public function startPreparing(Order $order): Order
    {
        $this->validateStatusTransition(
            $order,
            Order::STATUS_PENDING
        );

        $order->update([
            'status' => Order::STATUS_PREPARING,
        ]);

        // Notify chef that they're starting to prepare
        $this->notifyChefs(
            'order_preparing',
            'Order Started',
            'You started preparing order #' . $order->order_number
        );

        return $this->loadOrderRelations(
            $order->fresh()
        );
    }
    public function markReady(Order $order): Order
    {
        $this->validateStatusTransition(
            $order,
            Order::STATUS_PREPARING
        );
        $order->update([
            'status' => Order::STATUS_READY,
        ]);

        // Notify chef that order is ready
        $this->notifyChefs(
            'order_ready',
            'Order Ready',
            'Order #' . $order->order_number . ' is ready for pickup'
        );

        return $this->loadOrderRelations(
            $order->fresh()
        );
    }
    public function markServed(Order $order): Order{
    $this->validateStatusTransition(
        $order,
        Order::STATUS_READY
    );

    // Update order status first
    $order->update([
        'status' => Order::STATUS_SERVED,
        'served_at' => now(),
    ]);

    // Refresh the order with new status
    $order->refresh();

    // Notify chef that order is served
    $this->notifyChefs(
        'order_served',
        'Order Completed',
        'Order #' . $order->order_number . ' has been served'
    );

    // Create restaurant charge (this method has its own transaction)
    try {
        $this->restaurantChargeService->createFromOrder($order);
    } catch (\Exception $e) {
        // Log the error but don't fail the served status update
        \Log::warning('Failed to create restaurant charge for order ' . $order->id . ': ' . $e->getMessage());
    }

    return $this->loadOrderRelations($order);
}
    public function statistics(): array
    {
        return [
            'pending_orders' => Order::where(
                'status',
                Order::STATUS_PENDING
            )->count(),

            'preparing_orders' => Order::where(
                'status',
                Order::STATUS_PREPARING
            )->count(),

            'ready_orders' => Order::where(
                'status',
                Order::STATUS_READY
            )->count(),

            'served_orders' => Order::where(
                'status',
                Order::STATUS_SERVED
            )->count(),

            /*
            |--------------------------------------------------------------------------
            | Overall Statistics
            |--------------------------------------------------------------------------
            */

            'total_orders' => Order::count(),

            'today_orders' => Order::whereDate(
                'order_time',
                today()
            )->count(),

            'today_served' => Order::where(
                'status',
                Order::STATUS_SERVED
            )
            ->whereDate(
                'order_time',
                today()
            )
            ->count(),

            'today_pending' => Order::where(
                'status',
                Order::STATUS_PENDING
            )
            ->whereDate(
                'order_time',
                today()
            )
            ->count(),

            'today_preparing' => Order::where(
                'status',
                Order::STATUS_PREPARING
            )
            ->whereDate(
                'order_time',
                today()
            )
            ->count(),

            'today_ready' => Order::where(
                'status',
                Order::STATUS_READY
            )
            ->whereDate(
                'order_time',
                today()
            )
            ->count(),

        ];
    }
    /**
     * Notify all chefs with a message
     */
    protected function notifyChefs(string $type, string $title, string $message): void
    {
        try {
            $chefs = User::where('role', 'chef')->get();
            
            foreach ($chefs as $chef) {
                Notification::create([
                    'user_id' => $chef->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'read' => false,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to create chef notifications: ' . $e->getMessage());
        }
    }

    protected RestaurantChargeService $restaurantChargeService;

public function __construct(
    RestaurantChargeService $restaurantChargeService
)
{
    $this->restaurantChargeService = $restaurantChargeService;
}

}