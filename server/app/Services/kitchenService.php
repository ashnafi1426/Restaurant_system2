<?php

namespace App\Services;

use App\Events\OrderReadyEvent;
use App\Models\Order;
use App\Models\User;
use App\Models\Notification;
use App\Services\RestaurantChargeService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Log;

class KitchenService
{
    protected RestaurantChargeService $restaurantChargeService;

    public function __construct(RestaurantChargeService $restaurantChargeService)
    {
        $this->restaurantChargeService = $restaurantChargeService;
    }

    /**
     * Get categorized kitchen orders grouped by status for the active hotel.
     */
    public function getKitchenOrders($authUser = null): array
    {
        $orders = $this->getBaseKitchenQuery($authUser)
            ->with([
                'guest' => fn($gq) => $gq->withoutGlobalScopes(),
                'room' => fn($rq) => $rq->withoutGlobalScopes(),
                'reservation' => fn($rvq) => $rvq->withoutGlobalScopes(),
                'orderItems',
                'orderItems.menuItem' => fn($mq) => $mq->withoutGlobalScopes(),
                'table' => fn($tq) => $tq->withoutGlobalScopes(),
            ])
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
                Order::STATUS_SERVED,
            ])
            ->latest('order_time')
            ->get();

        return [
            'pending' => $orders->where('status', Order::STATUS_PENDING)->values(),
            'preparing' => $orders->where('status', Order::STATUS_PREPARING)->values(),
            'ready' => $orders->where('status', Order::STATUS_READY)->values(),
            'served' => $orders->where('status', Order::STATUS_SERVED)->values(),
        ];
    }

    /**
     * Build base query scoped by tenant/hotel.
     */
    protected function getBaseKitchenQuery($authUser = null)
    {
        $tenantContext = app(TenantContext::class);
        $hotelId = $tenantContext->getHotelId();

        if (!$hotelId && $authUser) {
            $hotelId = $authUser->hotel_id
                ?? \App\Models\HotelUser::where('user_id', $authUser->id)->value('hotel_id');
            if ($hotelId) {
                $tenantContext->setHotelId($hotelId);
            }
        }

        $query = Order::withoutGlobalScope(\App\Models\Scopes\TenantScope::class);

        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('orders.hotel_id', $hotelId)
                  ->orWhereHas('room', fn($rq) => $rq->withoutGlobalScopes()->where('hotel_id', $hotelId))
                  ->orWhereHas('table', fn($tq) => $tq->withoutGlobalScopes()->where('hotel_id', $hotelId));
            });
        }

        return $query;
    }

    /**
     * Start preparation on a pending order.
     */
    public function startPreparing(Order $order): Order
    {
        if ($order->status === Order::STATUS_PREPARING) {
            return $this->loadOrderRelations($order);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            throw new \InvalidArgumentException("Order must be 'pending' before starting preparation.");
        }

        $order->update(['status' => Order::STATUS_PREPARING]);

        return $this->loadOrderRelations($order->fresh());
    }

    /**
     * Mark order as ready and dispatch OrderReadyEvent.
     */
    public function markReady(Order $order): Order
    {
        if ($order->status === Order::STATUS_READY) {
            return $this->loadOrderRelations($order);
        }

        if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PREPARING])) {
            throw new \InvalidArgumentException("Order must be 'pending' or 'preparing' before marking ready.");
        }

        $order->update(['status' => Order::STATUS_READY]);
        $freshOrder = $this->loadOrderRelations($order->fresh());

        OrderReadyEvent::dispatch($freshOrder);

        $this->notifyChefs(
            'order',
            'Order Ready for Pickup',
            "Order #{$order->order_number} is ready for pickup/delivery.",
            $order
        );

        return $freshOrder;
    }

    /**
     * Mark order as served / completed.
     */
    public function markServed(Order $order): Order
    {
        if ($order->status !== Order::STATUS_READY) {
            throw new \InvalidArgumentException("Order must be 'ready' before marking as served.");
        }

        $order->update([
            'status' => Order::STATUS_SERVED,
            'served_at' => now(),
        ]);

        $this->notifyChefs(
            'order',
            'Order Completed',
            "Order #{$order->order_number} has been served.",
            $order
        );

        try {
            $this->restaurantChargeService->createFromOrder($order);
        } catch (\Throwable $e) {
            Log::warning("Restaurant charge creation failed for order #{$order->id}: {$e->getMessage()}");
        }

        return $this->loadOrderRelations($order->fresh());
    }

    /**
     * Calculate kitchen metrics for the current hotel in an optimized aggregate query.
     */
    public function statistics($authUser = null): array
    {
        $base = $this->getBaseKitchenQuery($authUser);
        $todayStr = today()->toDateString();

        $rows = (clone $base)
            ->selectRaw("
                status,
                COUNT(*) as total_count,
                SUM(CASE WHEN DATE(COALESCE(order_time, created_at)) = ? THEN 1 ELSE 0 END) as today_count
            ", [$todayStr])
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pending = $rows->get(Order::STATUS_PENDING);
        $preparing = $rows->get(Order::STATUS_PREPARING);
        $ready = $rows->get(Order::STATUS_READY);
        $served = $rows->get(Order::STATUS_SERVED);

        return [
            'pending_orders' => (int) ($pending->total_count ?? 0),
            'preparing_orders' => (int) ($preparing->total_count ?? 0),
            'ready_orders' => (int) ($ready->total_count ?? 0),
            'served_orders' => (int) ($served->total_count ?? 0),
            'total_orders' => (int) $rows->sum('total_count'),
            'today_orders' => (int) $rows->sum('today_count'),
            'today_pending' => (int) ($pending->today_count ?? 0),
            'today_preparing' => (int) ($preparing->today_count ?? 0),
            'today_ready' => (int) ($ready->today_count ?? 0),
            'today_served' => (int) ($served->today_count ?? 0),
        ];
    }

    /**
     * Eager-load relations needed by the kitchen dashboard.
     */
    protected function loadOrderRelations(Order $order): Order
    {
        return $order->load([
            'guest' => fn($gq) => $gq->withoutGlobalScopes(),
            'room' => fn($rq) => $rq->withoutGlobalScopes(),
            'reservation' => fn($rvq) => $rvq->withoutGlobalScopes(),
            'orderItems',
            'orderItems.menuItem' => fn($mq) => $mq->withoutGlobalScopes(),
            'table' => fn($tq) => $tq->withoutGlobalScopes(),
        ]);
    }

    /**
     * Notify chefs belonging to the order's hotel.
     */
    protected function notifyChefs(string $type, string $title, string $message, ?Order $order = null): void
    {
        try {
            $hotelId = $order?->hotel_id ?? app(TenantContext::class)->getHotelId();

            $chefQuery = User::where('role', 'chef');
            if ($hotelId) {
                $chefQuery->where(function ($q) use ($hotelId) {
                    $q->where('hotel_id', $hotelId)
                      ->orWhereHas('hotelMemberships', fn($m) => $m->where('hotel_id', $hotelId));
                });
            }

            $chefs = $chefQuery->get();

            foreach ($chefs as $chef) {
                Notification::create([
                    'user_id' => $chef->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'read' => false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create chef notifications: ' . $e->getMessage());
        }
    }
}