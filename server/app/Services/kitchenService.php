<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class KitchenService
{
    /**
     * --------------------------------------------------------------------------
     * Get all kitchen orders grouped by status
     * --------------------------------------------------------------------------
     */
    public function getKitchenOrders(): array
    {
        return [

            'pending' => $this->getOrdersByStatus(Order::STATUS_PENDING),

            'preparing' => $this->getOrdersByStatus(Order::STATUS_PREPARING),

            'ready' => $this->getOrdersByStatus(Order::STATUS_READY),

            'served' => $this->getOrdersByStatus(Order::STATUS_SERVED),

        ];
    }

    /**
     * --------------------------------------------------------------------------
     * Get orders by status
     * --------------------------------------------------------------------------
     */
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

    /**
     * --------------------------------------------------------------------------
     * Validate kitchen status transition
     * --------------------------------------------------------------------------
     */
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

    /**
     * --------------------------------------------------------------------------
     * Start Preparing Order
     * --------------------------------------------------------------------------
     */
    public function startPreparing(Order $order): Order
    {
        $this->validateStatusTransition(
            $order,
            Order::STATUS_PENDING
        );

        $order->update([
            'status' => Order::STATUS_PREPARING,
        ]);

        return $this->loadOrderRelations(
            $order->fresh()
        );
    }

    /**
     * --------------------------------------------------------------------------
     * Mark Order Ready
     * --------------------------------------------------------------------------
     */
    public function markReady(Order $order): Order
    {
        $this->validateStatusTransition(
            $order,
            Order::STATUS_PREPARING
        );

        $order->update([
            'status' => Order::STATUS_READY,
        ]);

        return $this->loadOrderRelations(
            $order->fresh()
        );
    }

    /**
     * --------------------------------------------------------------------------
     * Mark Order Served
     * --------------------------------------------------------------------------
     */
    public function markServed(Order $order): Order
    {
        $this->validateStatusTransition(
            $order,
            Order::STATUS_READY
        );

        $order->update([
            'status' => Order::STATUS_SERVED,
        ]);

        return $this->loadOrderRelations(
            $order->fresh()
        );
    }    /**
     * --------------------------------------------------------------------------
     * Kitchen Dashboard Statistics
     * --------------------------------------------------------------------------
     */
    public function statistics(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Orders by Status
            |--------------------------------------------------------------------------
            */

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

}