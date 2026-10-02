<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderStatusService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class KitchenService
{
    public function __construct(
        protected OrderStatusService $orderStatusService
    ) {}

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
     * Build base query scoped by tenant/hotel and staff access.
     */
    public function getBaseKitchenQuery($authUser = null): Builder
    {
        $tenantContext = app(TenantContext::class);
        $hotelId = $tenantContext->getHotelId();

        if (!$hotelId && $authUser) {
            $hotelId = \App\Models\HotelUser::where('user_id', $authUser->id)->where('is_active', true)->value('hotel_id');
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

        // Kitchen staff access restriction: chefs see their assigned orders or unassigned orders
        if ($authUser && in_array(strtolower($authUser->role ?? ''), ['chef', 'cook', 'kitchen_staff'])) {
            $query->where(function ($q) use ($authUser) {
                $q->where('orders.chef_id', $authUser->id)
                  ->orWhereNull('orders.chef_id');
            });
        }

        return $query;
    }

    /**
     * Start preparation on a pending order.
     */
    public function startPreparing(Order $order, ?User $actor = null): Order
    {
        return $this->orderStatusService->startPreparing($order, $actor);
    }

    /**
     * Mark order as ready and dispatch notifications to waiter.
     */
    public function markReady(Order $order): Order
    {
        return $this->orderStatusService->markReady($order);
    }

    /**
     * Mark order as served / completed.
     */
    public function markServed(Order $order): Order
    {
        return $this->orderStatusService->markServed($order);
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

        // Real average prep time for orders completed today
        $servedToday = (clone $base)
            ->where('status', Order::STATUS_SERVED)
            ->whereDate('served_at', today())
            ->whereNotNull('served_at')
            ->select(['id', 'order_time', 'created_at', 'served_at'])
            ->get();

        $avgPrepTimeMinutes = 0.0;
        if ($servedToday->isNotEmpty()) {
            $totalMins = $servedToday->sum(function ($o) {
                $start = $o->order_time ?? $o->created_at;
                return $start ? max(0, $start->diffInMinutes($o->served_at)) : 0;
            });
            $avgPrepTimeMinutes = round($totalMins / $servedToday->count(), 1);
        }

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
            'avg_prep_time_minutes' => $avgPrepTimeMinutes,
        ];
    }
}