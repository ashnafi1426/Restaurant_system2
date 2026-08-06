<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Services\RestaurantChargeService;
use Illuminate\Support\Facades\Log;

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
                // ❌ REMOVED: 'chef' relationship doesn't exist in Order model
            ])
            ->where('status', $status);
        
        // ❌ REMOVED: chef_id filtering since column doesn't exist
        // if ($authUser && isset($authUser->role) && $authUser->role === 'chef' && isset($authUser->id)) {
        //     $query->where(function($q) use ($authUser) {
        //         $q->where('chef_id', $authUser->id)
        //           ->orWhereNull('chef_id');
        //     });
        // }
        
        $results = $query->latest('order_time')->get();
        
        Log::info("📋 [KITCHEN SERVICE] Orders Query", [
            'status' => $status,
            'user_role' => $authUser->role ?? 'no-auth',
            'count' => $results->count(),
            'order_numbers' => $results->pluck('order_number')->toArray(),
        ]);
        
        return $results;
    }
    protected function loadOrderRelations(Order $order): Order
    {
        return $order->load([
            'guest',
            'room',
            'reservation',
            'orderItems',
            'orderItems.menuItem',
            // ❌ REMOVED: 'chef' relationship doesn't exist
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
        Log::info('Preparation Started', [
            'order_id' => $order->id,
            'current_status' => $order->status,
        ]);

        // If already preparing, just return it
        if ($order->status === Order::STATUS_PREPARING) {
            return $this->loadOrderRelations($order);
        }
        if ($order->status !== Order::STATUS_PENDING) {
            throw new \Exception("Order must be 'pending' before starting preparation.");
        }
        $order->update([
            'status' => Order::STATUS_PREPARING,
        ]);
        Log::info('Preparation Status Updated', [
            'order_id' => $order->id,
            'new_status' => $order->status,
        ]);

        $freshOrder = $this->loadOrderRelations($order->fresh());
        Log::info('Preparation Order Loaded', [
            'order_id' => $freshOrder->id,
            'status' => $freshOrder->status,
        ]);
        return $freshOrder;
    }
    public function markReady(Order $order): Order
    {
        Log::info('Order Ready Started', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'current_status' => $order->status,
        ]);
        if ($order->status === Order::STATUS_READY) {
            return $this->loadOrderRelations($order);
        }
        if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PREPARING])) {
            throw new \Exception("Order must be 'pending' or 'preparing' before marking ready.");
        }
        // STEP 2: Update order status to READY
        $order->update(['status' => Order::STATUS_READY]);
        Log::info('Order Status Updated', [
            'order_id' => $order->id,
            'new_status' => $order->status,
        ]);

        // STEP 3: Load order with relationships
        $order = $this->loadOrderRelations($order->fresh());

        // STEP 4: DISPATCH EVENT - Listener will handle waiter assignment
        \App\Events\OrderReadyEvent::dispatch($order);
        Log::info('Order Ready Event Dispatched', [
            'order_id' => $order->id,
        ]);

        // STEP 5: Notify chef that order is ready
        $this->notifyChefs(
            'order',
            'Order Ready for Pickup',
            "Order #{$order->order_number} is ready - waiter will pick it up"
        );

        // STEP 6: Return updated order
        $freshOrder = $this->loadOrderRelations($order->fresh());
        Log::info('Order Ready Completed', [
            'order_id' => $freshOrder->id,
            'status' => $freshOrder->status,
        ]);

        return $freshOrder;
    }
    public function markServed(Order $order): Order
    {
        Log::info('Order Served Started', [
            'order_id' => $order->id,
        ]);

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

        Log::info('Order Served Status Updated', [
            'order_id' => $order->id,
            'new_status' => $order->status,
        ]);

        // Notify chef that order is served
        $this->notifyChefs(
            'order',
            'Order Completed',
            "Order #{$order->order_number} has been served"
        );

        // Create restaurant charge (this method has its own transaction)
        try {
            $this->restaurantChargeService->createFromOrder($order);
        } catch (\Exception $e) {
            // Log the error but don't fail the served status update
            Log::warning("Restaurant Charge Creation Failed: {$e->getMessage()}", [
                'order_id' => $order->id,
            ]);
        }

        $freshOrder = $this->loadOrderRelations($order);
        
        Log::info('Order Served Completed', [
            'order_id' => $freshOrder->id,
            'status' => $freshOrder->status,
        ]);

        return $freshOrder;
    }
    public function statistics($authUser = null): array
    {
        // ✅ REMOVED chef_id filtering since column doesn't exist
        $baseQuery = function() use ($authUser) {
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
            Log::error('Failed to create chef notifications: ' . $e->getMessage());
        }
    }
    protected RestaurantChargeService $restaurantChargeService;
    public function __construct(RestaurantChargeService $restaurantChargeService)
    {
        $this->restaurantChargeService = $restaurantChargeService;
    }

}