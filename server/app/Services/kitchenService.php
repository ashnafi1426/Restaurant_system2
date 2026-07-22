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
    public function getKitchenOrders($authUser = null): array
    {
        return [

            'pending' => $this->getOrdersByStatus(Order::STATUS_PENDING, $authUser),

            'preparing' => $this->getOrdersByStatus(Order::STATUS_PREPARING, $authUser),

            'ready' => $this->getOrdersByStatus(Order::STATUS_READY, $authUser),

            'served' => $this->getOrdersByStatus(Order::STATUS_SERVED, $authUser),

        ];
    }
    protected function getOrdersByStatus(string $status, $authUser = null): Collection
    {
        $query = Order::query()
            ->with([
                'guest',
                'room',
                'reservation',
                'orderItems',
                'orderItems.menuItem',
                'chef',
            ])
            ->where('status', $status);

        // Filter by chef if user is a chef
        if ($authUser && isset($authUser->role) && $authUser->role === 'chef' && isset($authUser->id)) {
            // Show orders assigned to this chef OR orders without assignment (legacy orders)
            $query->where(function($q) use ($authUser) {
                $q->where('chef_id', $authUser->id)
                  ->orWhereNull('chef_id');
            });
        }

        return $query->latest('order_time')->get();
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
        \Log::info('🔵 [SERVICE] startPreparing - Validating order status', [
            'order_id' => $order->id,
            'current_status' => $order->status,
            'expected_status' => Order::STATUS_PENDING,
        ]);

        $this->validateStatusTransition(
            $order,
            Order::STATUS_PENDING
        );

        \Log::info('🟢 [SERVICE] startPreparing - Status validation passed', [
            'order_id' => $order->id,
        ]);

        $order->update([
            'status' => Order::STATUS_PREPARING,
        ]);

        \Log::info('✅ [SERVICE] startPreparing - Order status updated', [
            'order_id' => $order->id,
            'new_status' => $order->status,
        ]);

        // Notify chef that they're starting to prepare
        $this->notifyChefs(
            'order_preparing',
            'Order Started',
            'You started preparing order #' . $order->order_number
        );

        $freshOrder = $this->loadOrderRelations($order->fresh());
        
        \Log::info('✅ [SERVICE] startPreparing - Order reloaded with relations', [
            'order_id' => $freshOrder->id,
            'status' => $freshOrder->status,
            'has_items' => count($freshOrder->orderItems) > 0,
        ]);

        return $freshOrder;
    }
    public function markReady(Order $order): Order
    {
        \Log::info('🔵 [SERVICE] markReady - Validating order status', [
            'order_id' => $order->id,
            'current_status' => $order->status,
            'expected_status' => Order::STATUS_PREPARING,
        ]);

        $this->validateStatusTransition(
            $order,
            Order::STATUS_PREPARING
        );

        \Log::info('🟢 [SERVICE] markReady - Status validation passed', [
            'order_id' => $order->id,
        ]);

        $order->update([
            'status' => Order::STATUS_READY,
        ]);

        \Log::info('✅ [SERVICE] markReady - Order status updated', [
            'order_id' => $order->id,
            'new_status' => $order->status,
        ]);

        // Notify chef that order is ready
        $this->notifyChefs(
            'order_ready',
            'Order Ready',
            'Order #' . $order->order_number . ' is ready for pickup'
        );

        $freshOrder = $this->loadOrderRelations($order->fresh());
        
        \Log::info('✅ [SERVICE] markReady - Order reloaded with relations', [
            'order_id' => $freshOrder->id,
            'status' => $freshOrder->status,
        ]);

        return $freshOrder;
    }
    public function markServed(Order $order): Order{
    \Log::info('🔵 [SERVICE] markServed - Validating order status', [
        'order_id' => $order->id,
        'current_status' => $order->status,
        'expected_status' => Order::STATUS_READY,
    ]);

    $this->validateStatusTransition(
        $order,
        Order::STATUS_READY
    );

    \Log::info('🟢 [SERVICE] markServed - Status validation passed', [
        'order_id' => $order->id,
    ]);

    // Update order status first
    $order->update([
        'status' => Order::STATUS_SERVED,
        'served_at' => now(),
    ]);

    // Refresh the order with new status
    $order->refresh();

    \Log::info('✅ [SERVICE] markServed - Order status updated', [
        'order_id' => $order->id,
        'new_status' => $order->status,
    ]);

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

    $freshOrder = $this->loadOrderRelations($order);
    
    \Log::info('✅ [SERVICE] markServed - Order reloaded with relations', [
        'order_id' => $freshOrder->id,
        'status' => $freshOrder->status,
    ]);

    return $freshOrder;
}
    public function statistics($authUser = null): array
    {
        $baseQuery = function() use ($authUser) {
            if ($authUser && isset($authUser->role) && $authUser->role === 'chef') {
                // Show stats for orders assigned to this chef OR orders without assignment
                return Order::where(function($q) use ($authUser) {
                    $q->where('chef_id', $authUser->id)
                      ->orWhereNull('chef_id');
                });
            }
            return Order::query();
        };

        return [
            'pending_orders' => $baseQuery()->where(
                'status',
                Order::STATUS_PENDING
            )->count(),

            'preparing_orders' => $baseQuery()->where(
                'status',
                Order::STATUS_PREPARING
            )->count(),

            'ready_orders' => $baseQuery()->where(
                'status',
                Order::STATUS_READY
            )->count(),

            'served_orders' => $baseQuery()->where(
                'status',
                Order::STATUS_SERVED
            )->count(),

            /*
            |--------------------------------------------------------------------------
            | Overall Statistics
            |--------------------------------------------------------------------------
            */

            'total_orders' => $baseQuery()->count(),

            'today_orders' => $baseQuery()->whereDate(
                'order_time',
                today()
            )->count(),

            'today_served' => $baseQuery()->where(
                'status',
                Order::STATUS_SERVED
            )
            ->whereDate(
                'order_time',
                today()
            )
            ->count(),

            'today_pending' => $baseQuery()->where(
                'status',
                Order::STATUS_PENDING
            )
            ->whereDate(
                'order_time',
                today()
            )
            ->count(),

            'today_preparing' => $baseQuery()->where(
                'status',
                Order::STATUS_PREPARING
            )
            ->whereDate(
                'order_time',
                today()
            )
            ->count(),

            'today_ready' => $baseQuery()->where(
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