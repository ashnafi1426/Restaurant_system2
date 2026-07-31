<?php

namespace App\Services\Manager;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KitchenMonitorService
{
    public function getOrders($filters = [], $perPage = 15)
    {
        $query = Order::query();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereNotIn('status', ['cancelled']);
        }
        if (!empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        } else {
            $query->whereDate('created_at', Carbon::today());
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }
    public function getMetrics(): array
    {
        $today = Carbon::today();

        $totalOrders = Order::whereDate('created_at', $today)
            ->whereIn('status', ['preparing', 'ready', 'completed'])
            ->count();

        $readyOrders = Order::whereDate('created_at', $today)
            ->where('status', 'ready')
            ->count();

        $preparingOrders = Order::whereDate('created_at', $today)
            ->where('status', 'preparing')
            ->count();

        $delayedOrders = Order::whereDate('created_at', $today)
            ->where('status', 'preparing')
            ->where('created_at', '<', now()->subMinutes(30))
            ->count();

        $completedOrders = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        return [
            'total_orders' => $totalOrders,
            'ready_orders' => $readyOrders,
            'preparing_orders' => $preparingOrders,
            'delayed_orders' => $delayedOrders,
            'completed_orders' => $completedOrders,
            'avg_prep_time' => $this->getAveragePrepTime(),
            'prep_time_trend' => $this->getPrepTimeTrend(),
        ];
    }
    public function getDelayedOrders()
    {
        return Order::whereDate('created_at', Carbon::today())
            ->where('status', 'preparing')
            ->where('created_at', '<', now()->subMinutes(30))
            ->with('guest', 'items')
            ->orderBy('created_at', 'asc')
            ->get();
    }
    public function getChefWorkload(): array
    {
        $today = Carbon::today();

        return DB::table('orders')
            ->whereDate('created_at', $today)
            ->whereIn('status', ['preparing', 'ready'])
            ->select(DB::raw('COUNT(*) as order_count'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('order_count', 'desc')
            ->get()
            ->toArray();
    }
    public function getPerformance(): array
    {
        $today = Carbon::today();

        $completedOrders = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        $totalOrders = Order::whereDate('created_at', $today)
            ->whereIn('status', ['preparing', 'ready', 'completed'])
            ->count();

        $completionRate = $totalOrders > 0 
            ? round(($completedOrders / $totalOrders) * 100, 2)
            : 0;

        return [
            'completion_rate' => $completionRate,
            'avg_prep_time' => $this->getAveragePrepTime(),
            'delayed_percentage' => $this->getDelayedPercentage(),
            'quality_score' => $this->getQualityScore(),
        ];
    }
    private function getAveragePrepTime(): ?float
    {
        $avgTime = Order::where('status', 'completed')
            ->where('completed_at', '>=', Carbon::today())
            ->average(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, completed_at)'));

        return $avgTime ? round($avgTime, 2) : null;
    }
    private function getPrepTimeTrend(): array
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $avgTime = Order::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->average(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, completed_at)'));

            $trend[] = [
                'date' => $date,
                'avg_time' => $avgTime ? round($avgTime, 2) : 0,
            ];
        }

        return $trend;
    }
    private function getDelayedPercentage(): float
    {
        $today = Carbon::today();

        $delayedOrders = Order::whereDate('created_at', $today)
            ->where('status', 'preparing')
            ->where('created_at', '<', now()->subMinutes(30))
            ->count();

        $totalOrders = Order::whereDate('created_at', $today)
            ->whereIn('status', ['preparing', 'ready', 'completed'])
            ->count();

        return $totalOrders > 0 
            ? round(($delayedOrders / $totalOrders) * 100, 2)
            : 0;
    }
    private function getQualityScore(): float
    {
        $today = Carbon::today();

        $completedOrders = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        $rejectedOrders = Order::whereDate('created_at', $today)
            ->where('status', 'cancelled')
            ->count();

        if ($completedOrders === 0) {
            return 100;
        }

        $qualityScore = (1 - ($rejectedOrders / ($completedOrders + $rejectedOrders))) * 100;
        return round($qualityScore, 2);
    }
    public function getTopPreparedItems($limit = 5): array
    {
        $today = Carbon::today();

        return DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $today)
            ->select('menu_items.name', DB::raw('SUM(order_items.quantity) as total_prepared'))
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderByDesc('total_prepared')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    public function getQueueStatus(): array
    {
        $today = Carbon::today();

        $queues = [
            'immediate' => Order::whereDate('created_at', $today)
                ->where('status', 'pending')
                ->count(),
            'preparing' => Order::whereDate('created_at', $today)
                ->where('status', 'preparing')
                ->count(),
            'ready' => Order::whereDate('created_at', $today)
                ->where('status', 'ready')
                ->count(),
            'delivering' => Order::whereDate('created_at', $today)
                ->where('status', 'delivery')
                ->count(),
        ];

        $total = array_sum($queues);

        return [
            'queue_status' => $queues,
            'total_in_queue' => $total,
            'estimated_wait_time' => $this->getAveragePrepTime() ?? 0,
        ];
    }
}
