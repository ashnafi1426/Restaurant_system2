<?php

namespace App\Services\Waiter;

use App\Models\DeliveryLog;
use App\Models\WaiterPerformance;
use Carbon\Carbon;
use App\Models\WaiterFloorAssignment;
use App\Models\Waiter;
use App\Models\Order;
use App\Models\DeliveryTask;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Cache;


class WaiterDashboardService
{
    public function getWaiterAssignedFloorIds($waiterId): array
    {
        if (!$waiterId) {
            return [];
        }

        $hotelId = app(TenantContext::class)->getHotelId();
        $cacheKey = $hotelId ? "waiter_floor_ids:{$hotelId}:{$waiterId}" : "waiter_floor_ids:{$waiterId}";
        
        return Cache::remember($cacheKey, 60, function () use ($waiterId, $hotelId) {
            try {
                $query = WaiterFloorAssignment::where('waiter_id', $waiterId);
                
                // Apply hotel_id filter for tenant isolation
                if ($hotelId) {
                    $query->where('hotel_id', $hotelId);
                }
                
                return $query->where(function ($q) {
                        $q->where('is_active', true)
                          ->orWhere('status', 'active');
                    })
                    ->pluck('floor_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            } catch (\Throwable $e) {
                \Log::error('Error resolving waiter assigned floor IDs: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function clearWaiterFloorCache($waiterId): void
    {
        $hotelId = app(TenantContext::class)->getHotelId();
        $cacheKey = $hotelId ? "waiter_floor_ids:{$hotelId}:{$waiterId}" : "waiter_floor_ids:{$waiterId}";
        Cache::forget($cacheKey);
    }

    public function getWaiterAssignedTableIds($waiterId): array
    {
        if (!$waiterId) {
            return [];
        }

        $hotelId = app(TenantContext::class)->getHotelId();
        $cacheKey = $hotelId ? "waiter_table_ids:{$hotelId}:{$waiterId}" : "waiter_table_ids:{$waiterId}";

        return Cache::remember($cacheKey, 60, function () use ($waiterId, $hotelId) {
            try {
                $query = \App\Models\WaiterTableAssignment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                    ->where('waiter_id', $waiterId)
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereDate('assignment_date', today())
                          ->orWhereNull('assignment_date');
                    });

                if ($hotelId) {
                    $query->where(function ($q) use ($hotelId) {
                        $q->where('hotel_id', $hotelId)
                          ->orWhereNull('hotel_id');
                    });
                }

                return $query->pluck('table_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            } catch (\Throwable $e) {
                \Log::error('Error resolving waiter assigned table IDs: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function clearWaiterTableCache($waiterId): void
    {
        $hotelId = app(TenantContext::class)->getHotelId();
        $cacheKey = $hotelId ? "waiter_table_ids:{$hotelId}:{$waiterId}" : "waiter_table_ids:{$waiterId}";
        Cache::forget($cacheKey);
    }

    public function getDashboardStats($waiterId = null): array
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $cacheKey = "waiter_dashboard_stats:" . ($hotelId ?? 'all') . ":" . ($waiterId ?? auth()->id() ?? 'all');

            if (request()->query('refresh') !== 'true') {
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }
            }

            $todayStats = $this->getTodayStats($waiterId);
            $recentAssignments = $this->getRecentAssignments($waiterId, 8);

            // Find active delivery in-memory without extra round-trip query
            $activeDelivery = null;
            foreach ($recentAssignments as $assignment) {
                if (in_array($assignment['status'] ?? '', ['on_delivery', 'picked_up'])) {
                    $activeDelivery = $assignment;
                    break;
                }
            }

            // Derive counts from todayStats without re-querying the database
            $pendingCount = (int) ($todayStats['pending_assignments'] ?? 0);
            $activeCount = (int) ($todayStats['active_assignments'] ?? 0);

            // Lightweight initial performance summary (avoids 30-day database table scans on dashboard load)
            $performance = [
                'today' => [
                    'deliveries' => (int) ($todayStats['completed_deliveries'] ?? 0),
                    'failed' => (int) ($todayStats['failed_deliveries'] ?? 0),
                    'average_delivery_time' => (float) ($todayStats['average_delivery_time'] ?? 0),
                    'rating' => 4.8,
                    'guest_rating' => 4.8,
                    'success_rate' => $todayStats['completion_rate'] ?? 100,
                ],
            ];

            $result = [
                'today_stats' => $todayStats,
                'performance' => $performance,
                'recent_assignments' => $recentAssignments,
                'active_delivery' => $activeDelivery,
                'pending_count' => $pendingCount,
                'active_count' => $activeCount,
            ];

            Cache::put($cacheKey, $result, now()->addSeconds(30));

            return $result;
        } catch (\Throwable $e) {
            \Log::error(' Dashboard stats error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [
                'today_stats' => $this->getDefaultTodayStats(),
                'performance' => $this->getDefaultPerformanceMetrics(),
                'recent_assignments' => [],
                'pending_count' => 0,
                'active_count' => 0,
            ];
        }
    }

    public function getTodayStats($waiterId = null): array
    {
        try {
            $today = Carbon::today();
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
            $assignedFloorIds = $waiterId ? $this->getWaiterAssignedFloorIds($waiterId) : [];
            $assignedTableIds = $waiterId ? $this->getWaiterAssignedTableIds($waiterId) : [];
            $hasAssignments = !empty($assignedTableIds) || !empty($assignedFloorIds);

            $taskQuery = DeliveryTask::withoutGlobalScope(TenantScope::class);

            // CRITICAL: Apply hotel_id filter for tenant isolation
            if ($hotelId) {
                $taskQuery->where('delivery_tasks.hotel_id', $hotelId);
            }

            if ($waiterId && (!$isAdminOrManager || $hasAssignments)) {
                $taskQuery->where(function($q) use ($waiterId) {
                    $q->where('delivery_tasks.waiter_id', $waiterId)
                      ->orWhere('delivery_tasks.waiter_id', auth()->id());
                });
            }

            // Single combined query for both historical and current stats
            $allStats = $taskQuery->selectRaw('
                -- Today historical
                SUM(CASE WHEN DATE(COALESCE(delivered_at, assigned_at, created_at)) = CURDATE() THEN 1 ELSE 0 END) as total_assignments,
                SUM(CASE WHEN status = "delivered" AND DATE(delivered_at) = CURDATE() THEN 1 ELSE 0 END) as completed_deliveries,
                SUM(CASE WHEN status = "cancelled" AND DATE(COALESCE(cancelled_at, created_at)) = CURDATE() THEN 1 ELSE 0 END) as failed_deliveries,
                -- Current active stats
                SUM(CASE WHEN status IN ("assigned", "waiting_assignment", "accepted") THEN 1 ELSE 0 END) as pending_assignments,
                SUM(CASE WHEN status IN ("assigned", "waiting_assignment", "accepted", "picked_up", "on_delivery") THEN 1 ELSE 0 END) as active_assignments,
                SUM(CASE WHEN status IN ("picked_up", "on_delivery") THEN 1 ELSE 0 END) as on_delivery_count,
                -- Average delivery time
                ROUND(AVG(CASE 
                    WHEN status = "delivered" AND DATE(delivered_at) = CURDATE() 
                    THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) 
                    ELSE NULL 
                END), 2) as average_delivery_time
            ')->first();

            // Get picked up order IDs with hotel_id filter
            $pickedUpQuery = DeliveryTask::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->whereIn('status', ['picked_up', 'on_delivery', 'delivered', 'cancelled']);
            
            if ($hotelId) {
                $pickedUpQuery->where('delivery_tasks.hotel_id', $hotelId);
            }
            
            $pickedUpOrderIds = $pickedUpQuery->pluck('order_id')
                ->filter()
                ->toArray();

            $orderReadyQuery = Order::withoutGlobalScope(TenantScope::class)
                ->whereIn('status', ['ready', 'pending', 'preparing']);
            if (!empty($pickedUpOrderIds)) {
                $orderReadyQuery->whereNotIn('orders.id', $pickedUpOrderIds);
            }
            if ($hotelId) {
                $orderReadyQuery->where('orders.hotel_id', $hotelId);
            }
            if ($waiterId && (!$isAdminOrManager || $hasAssignments)) {
                $orderReadyQuery->where(function ($q) use ($waiterId, $assignedFloorIds, $assignedTableIds, $hasAssignments) {
                    $q->whereHas('deliveryTasks', fn($dt) => $dt->where('waiter_id', $waiterId));
                    if (!empty($assignedFloorIds)) {
                        $q->orWhereHas('room', fn($rq) => $rq->whereIn('floor_id', $assignedFloorIds));
                    }
                    if (!empty($assignedTableIds)) {
                        $q->orWhereIn('table_id', $assignedTableIds);
                    }
                    if (!$hasAssignments) {
                        $q->orWhereDoesntHave('deliveryTasks', fn($dt) => $dt->whereNotNull('waiter_id'));
                    }
                });
            }
            $kitchenReadyCount = $orderReadyQuery->count();

            $completedCount = (int)($allStats->completed_deliveries ?? 0);
            $pendingCount = (int)($allStats->pending_assignments ?? 0);
            $pendingPickup = max($pendingCount, $kitchenReadyCount);
            $onDelivery = (int)($allStats->on_delivery_count ?? 0);
            $activeCount = (int)($allStats->active_assignments ?? $onDelivery);
            $totalCount = (int)($allStats->total_assignments ?? ($completedCount + $pendingPickup + $onDelivery));

            $avgTime = (float)($allStats->average_delivery_time ?? 0);

            $completionRate = ($completedCount + $onDelivery) > 0 
                ? round(($completedCount / max(1, $completedCount + $onDelivery)) * 100, 1) 
                : 0.0;

            return [
                'total_assignments' => $totalCount,
                'completed_deliveries' => $completedCount,
                'failed_deliveries' => (int)($allStats->failed_deliveries ?? 0),
                'rejected_assignments' => 0,
                'pending_assignments' => $pendingPickup,
                'active_assignments' => $activeCount,
                'on_delivery_count' => $onDelivery,
                'average_delivery_time' => $avgTime > 0 ? $avgTime : 0,
                'completion_rate' => $completionRate,
            ];
        } catch (\Throwable $e) {
            \Log::error(' Today stats error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->getDefaultTodayStats();
        }
    }

    private function getDefaultTodayStats(): array
    {
        return [
            'total_assignments' => 0,
            'completed_deliveries' => 0,
            'failed_deliveries' => 0,
            'rejected_assignments' => 0,
            'pending_assignments' => 0,
            'active_assignments' => 0,
            'on_delivery_count' => 0,
            'average_delivery_time' => 0,
            'completion_rate' => 0,
        ];
    }

    public function getPerformanceMetrics($waiterId): array
    {
        try {
            $now = Carbon::now();
            $today = Carbon::today();
            $hotelId = app(TenantContext::class)->getHotelId();
            
            $userIds = [$waiterId];
            if (auth()->check()) {
                $userIds[] = auth()->id();
            }
            $waiterModel =Waiter::find($waiterId);
            if ($waiterModel) {
                $userIds[] = $waiterModel->user_id;
            } else {
                $waiterModelByUser =Waiter::where('user_id', $waiterId)->first();
                if ($waiterModelByUser) {
                    $userIds[] = $waiterModelByUser->id;
                }
            }
            $userIds = array_values(array_unique(array_filter($userIds)));

            // Apply hotel_id filter for tenant isolation
            $performanceQuery = WaiterPerformance::whereIn('waiter_id', $userIds);
            if ($hotelId) {
                $performanceQuery->where('hotel_id', $hotelId);
            }

            $todayPerformance = (clone $performanceQuery)
                ->where('metric_date', $today)
                ->first();

            // Fetch both week and month performance in one query
            $allPerformance = (clone $performanceQuery)
                ->where('metric_date', '>=', $now->copy()->subDays(30)->startOfDay())
                ->get();
            
            $weekPerformance = $allPerformance->filter(fn($p) => $p->metric_date >= $now->copy()->subDays(7)->startOfDay());
            $monthPerformance = $allPerformance;

            // Single aggregated query for all time windows with hotel_id filter
            $taskMetricsQuery = DeliveryTask::whereIn('waiter_id', $userIds);
            
            if ($hotelId) {
                $taskMetricsQuery->where('delivery_tasks.hotel_id', $hotelId);
            }
            
            $taskMetrics = $taskMetricsQuery->selectRaw('
                    SUM(CASE WHEN status = "delivered" AND DATE(delivered_at) = CURDATE() THEN 1 ELSE 0 END) as today_completed,
                    SUM(CASE WHEN status IN ("failed", "cancelled") AND DATE(COALESCE(cancelled_at, created_at)) = CURDATE() THEN 1 ELSE 0 END) as today_failed,
                    ROUND(AVG(CASE WHEN status = "delivered" AND DATE(delivered_at) = CURDATE() AND TIMESTAMPDIFF(MINUTE, COALESCE(assigned_at, created_at), delivered_at) > 0 
                        THEN TIMESTAMPDIFF(MINUTE, COALESCE(assigned_at, created_at), delivered_at) 
                        ELSE NULL 
                    END), 1) as today_avg_time,
                    SUM(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_completed,
                    SUM(CASE WHEN status IN ("failed", "cancelled") AND COALESCE(cancelled_at, created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_failed,
                    ROUND(AVG(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND TIMESTAMPDIFF(MINUTE, COALESCE(assigned_at, created_at), delivered_at) > 0 
                        THEN TIMESTAMPDIFF(MINUTE, COALESCE(assigned_at, created_at), delivered_at) 
                        ELSE NULL 
                    END), 1) as week_avg_time,
                    SUM(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_completed,
                    SUM(CASE WHEN status IN ("failed", "cancelled") AND COALESCE(cancelled_at, created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_failed,
                    ROUND(AVG(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND TIMESTAMPDIFF(MINUTE, COALESCE(assigned_at, created_at), delivered_at) > 0 
                        THEN TIMESTAMPDIFF(MINUTE, COALESCE(assigned_at, created_at), delivered_at) 
                        ELSE NULL 
                    END), 1) as month_avg_time
                ')
                ->first();

            $todayDeliveries = max($todayPerformance?->deliveries_completed ?? 0, $taskMetrics->today_completed ?? 0);
            $todayFailed = max($todayPerformance?->deliveries_failed ?? 0, $taskMetrics->today_failed ?? 0);
            $todayAvgTime = $todayPerformance?->avg_delivery_time_minutes ?? ($taskMetrics->today_avg_time ?? 15);

            $weekDeliveries = max($weekPerformance->sum('deliveries_completed'), $taskMetrics->week_completed ?? 0);
            $weekFailed = max($weekPerformance->sum('deliveries_failed'), $taskMetrics->week_failed ?? 0);
            $weekAvgTime = $this->calculateAverageMetric($weekPerformance, 'avg_delivery_time_minutes') ?: ($taskMetrics->week_avg_time ?? 18);

            $monthDeliveries = max($monthPerformance->sum('deliveries_completed'), $taskMetrics->month_completed ?? 0);
            $monthFailed = max($monthPerformance->sum('deliveries_failed'), $taskMetrics->month_failed ?? 0);
            $monthAvgTime = $this->calculateAverageMetric($monthPerformance, 'avg_delivery_time_minutes') ?: ($taskMetrics->month_avg_time ?? 16);

            return [
                'today' => [
                    'deliveries' => $todayDeliveries,
                    'failed' => $todayFailed,
                    'average_delivery_time' => $todayAvgTime ?: 15,
                    'rating' => $todayPerformance?->rating ?? 4.8,
                    'guest_rating' => $todayPerformance?->guest_rating_avg ?? 4.8,
                    'success_rate' => ($todayDeliveries + $todayFailed) > 0 ? round(($todayDeliveries / ($todayDeliveries + $todayFailed)) * 100, 1) : 100,
                ],
                'week' => [
                    'deliveries' => $weekDeliveries,
                    'failed' => $weekFailed,
                    'average_delivery_time' => $weekAvgTime ?: 18,
                    'rating' => $this->calculateAverageMetric($weekPerformance, 'rating') ?: 4.8,
                    'guest_rating' => $this->calculateAverageMetric($weekPerformance, 'guest_rating_avg') ?: 4.8,
                    'success_rate' => ($weekDeliveries + $weekFailed) > 0 ? round(($weekDeliveries / ($weekDeliveries + $weekFailed)) * 100, 1) : 100,
                ],
                'month' => [
                    'deliveries' => $monthDeliveries,
                    'failed' => $monthFailed,
                    'average_delivery_time' => $monthAvgTime ?: 16,
                    'rating' => $this->calculateAverageMetric($monthPerformance, 'rating') ?: 4.8,
                    'guest_rating' => $this->calculateAverageMetric($monthPerformance, 'guest_rating_avg') ?: 4.8,
                    'success_rate' => ($monthDeliveries + $monthFailed) > 0 ? round(($monthDeliveries / ($monthDeliveries + $monthFailed)) * 100, 1) : 100,
                ],
            ];
        } catch (\Throwable $e) {
            \Log::error('Performance metrics error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->getDefaultPerformanceMetrics();
        }
    }

    private function getDefaultPerformanceMetrics(): array
    {
        return [
            'today' => [
                'deliveries' => 0,
                'failed' => 0,
                'average_delivery_time' => 0,
                'rating' => 0,
                'guest_rating' => 0,
            ],
            'week' => [
                'deliveries' => 0,
                'failed' => 0,
                'average_delivery_time' => 0,
                'rating' => 0,
                'guest_rating' => 0,
            ],
            'month' => [
                'deliveries' => 0,
                'failed' => 0,
                'average_delivery_time' => 0,
                'rating' => 0,
                'guest_rating' => 0,
            ],
        ];
    }

    public function getRecentAssignments($waiterId = null, $limit = 10): array
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
            $assignedFloorIds = $waiterId ? $this->getWaiterAssignedFloorIds($waiterId) : [];
            $assignedTableIds = $waiterId ? $this->getWaiterAssignedTableIds($waiterId) : [];
            $hasAssignments = !empty($assignedTableIds) || !empty($assignedFloorIds);

            $baseQuery = DeliveryTask::withoutGlobalScope(TenantScope::class);
            
            // CRITICAL: Apply hotel_id filter for tenant isolation
            if ($hotelId) {
                $baseQuery->where('delivery_tasks.hotel_id', $hotelId);
            }

            if ($waiterId && (!$isAdminOrManager || $hasAssignments)) {
                $baseQuery->where(function ($q) use ($waiterId, $assignedFloorIds, $assignedTableIds, $hasAssignments) {
                    $q->where('delivery_tasks.waiter_id', $waiterId);

                    if (!empty($assignedFloorIds)) {
                        $q->orWhere(function ($sub) use ($assignedFloorIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($f) use ($assignedFloorIds) {
                                $f->whereIn('delivery_tasks.floor_id', $assignedFloorIds)
                                  ->orWhereHas('order.room', fn($r) => $r->whereIn('floor_id', $assignedFloorIds));
                            })->where('delivery_tasks.status', 'waiting_assignment');
                        });
                    }

                    if (!empty($assignedTableIds)) {
                        $q->orWhere(function ($sub) use ($assignedTableIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($t) use ($assignedTableIds) {
                                $t->whereIn('delivery_tasks.table_id', $assignedTableIds)
                                  ->orWhereHas('order', fn($o) => $o->whereIn('table_id', $assignedTableIds));
                            })->where('delivery_tasks.status', 'waiting_assignment');
                        });
                    }

                    if (!$hasAssignments) {
                        $q->orWhere(function ($sub) {
                            $sub->whereNull('delivery_tasks.waiter_id')
                                ->where('delivery_tasks.status', 'waiting_assignment');
                        });
                    }
                });
            }

            $deliveryTasks = (clone $baseQuery)
                ->with([
                    'room:id,room_number,floor_id',
                    'floor:id,floor_number',
                    'table:id,table_number,section',
                    'order:id,order_number,room_id,table_id,order_type,guest_id,notes,special_requests',
                    'order.table:id,table_number,section',
                    'order.guest:id,first_name,last_name',
                    'order.orderItems:id,order_id,quantity'
                ])
                ->select([
                    'id', 'order_id', 'room_id', 'floor_id', 'table_id', 'status', 'assignment_type',
                    'assigned_at', 'accepted_at', 'picked_up_at', 'on_delivery_at', 
                    'delivered_at', 'remarks', 'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            if ($deliveryTasks->isEmpty()) {
                return [];
            } 

            return $deliveryTasks->map(function ($delivery) {
                    $orderNumber = $delivery->order?->order_number
                        ?? 'ORD-' . substr($delivery->id, 0, 8);

                    $tableNumber = $delivery->table?->table_number ?? $delivery->order?->table?->table_number ?? null;
                    $isTableOrder = !empty($tableNumber) || !empty($delivery->table_id) || !empty($delivery->order?->table_id);
                    $roomNumber = !$isTableOrder ? ($delivery->room?->room_number ?? 'N/A') : null;
                    $destination = $tableNumber ? "Table {$tableNumber}" : ($roomNumber && $roomNumber !== 'N/A' ? "Room {$roomNumber}" : ($isTableOrder ? 'Table Order' : 'Room Service'));

                    $guest = $delivery->order?->guest;
                    $guestName = $guest ? trim($guest->first_name . ' ' . $guest->last_name) : ($tableNumber ? "Table {$tableNumber} Guest" : ($roomNumber && $roomNumber !== 'N/A' ? 'Guest Room ' . $roomNumber : 'Guest'));

                    $itemCount = $delivery->order?->orderItems?->count() ?? 1;

                    return [
                        'id' => $delivery->id,
                        'order_id' => $delivery->order_id,
                        'room_id' => $delivery->room_id,
                        'room_number' => $roomNumber,
                        'table_id' => $delivery->table_id ?? $delivery->order?->table_id,
                        'table_number' => $tableNumber,
                        'destination' => $destination,
                        'order_type' => $delivery->order?->order_type ?? ($isTableOrder ? 'dine_in' : 'room_service'),
                        'floor_id' => $delivery->floor_id,
                        'floor_number' => $delivery->floor?->floor_number,
                        'guest_name' => $guestName,
                        'order_number' => $orderNumber,
                        'items' => $itemCount,
                        'status' => $delivery->status,
                        'order_status' => $delivery->status,
                        'assignment_type' => $delivery->assignment_type ?? 'manual',
                        'assigned_at' => ($delivery->assigned_at ?? $delivery->created_at)?->format('Y-m-d H:i:s'),
                        'accepted_at' => $delivery->accepted_at?->format('Y-m-d H:i:s'),
                        'picked_up_at' => $delivery->picked_up_at?->format('Y-m-d H:i:s'),
                        'on_delivery_at' => $delivery->on_delivery_at?->format('Y-m-d H:i:s'),
                        'delivered_at' => $delivery->delivered_at?->format('Y-m-d H:i:s'),
                        'delivery_time_minutes' => $delivery->getDeliveryDurationMinutes(),
                        'is_late' => $delivery->isLate(),
                        'remarks' => $delivery->remarks ?? 'None',
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            \Log::error(' Recent assignments error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    private function buildDeliveryPipeline($assignment): array
    {
        $pipeline = [
            [
                'stage' => 'pending',
                'label' => 'Order Created',
                'icon' => 'Clock',
                'timestamp' => $assignment->assigned_at,
                'completed' => $assignment->status !== 'pending',
                'duration' => null,
            ],
            [
                'stage' => 'accepted',
                'label' => 'Waiter Accepted',
                'icon' => 'CheckCircle',
                'timestamp' => $assignment->accepted_at,
                'completed' => in_array($assignment->status, ['accepted', 'picked_up', 'on_delivery', 'delivered']),
                'duration' => $assignment->accepted_at && $assignment->assigned_at 
                    ? $assignment->assigned_at->diffInMinutes($assignment->accepted_at)
                    : null,
            ],
            [
                'stage' => 'picked_up',
                'label' => 'Food Picked Up from Kitchen',
                'icon' => 'Package',
                'timestamp' => $assignment->picked_up_at,
                'completed' => in_array($assignment->status, ['picked_up', 'on_delivery', 'delivered']),
                'duration' => $assignment->picked_up_at && $assignment->accepted_at 
                    ? $assignment->accepted_at->diffInMinutes($assignment->picked_up_at)
                    : null,
            ],
            [
                'stage' => 'on_delivery',
                'label' => 'On the Way to Room',
                'icon' => 'Truck',
                'timestamp' => null,
                'completed' => in_array($assignment->status, ['on_delivery', 'delivered']),
                'duration' => $assignment->picked_up_at 
                    ? ($assignment->status === 'delivered' 
                        ? $assignment->picked_up_at->diffInMinutes($assignment->delivered_at)
                        : now()->diffInMinutes($assignment->picked_up_at))
                    : null,
            ],
            [
                'stage' => 'delivered',
                'label' => 'Delivered to Guest',
                'icon' => 'Home',
                'timestamp' => $assignment->delivered_at,
                'completed' => $assignment->status === 'delivered',
                'duration' => null,
            ],
        ];

        if ($assignment->status === 'failed') {
            $pipeline[] = [
                'stage' => 'failed',
                'label' => 'Delivery Failed',
                'icon' => 'AlertCircle',
                'timestamp' => $assignment->failed_at,
                'completed' => true,
                'reason' => $assignment->failure_reason,
                'duration' => $assignment->failed_at && $assignment->assigned_at 
                    ? $assignment->assigned_at->diffInMinutes($assignment->failed_at)
                    : null,
            ];
        }

        return $pipeline;
    }

    public function getPendingCount($waiterId = null): int
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
            $assignedFloorIds = $waiterId ? $this->getWaiterAssignedFloorIds($waiterId) : [];
            $assignedTableIds = $waiterId ? $this->getWaiterAssignedTableIds($waiterId) : [];
            $hasAssignments = !empty($assignedTableIds) || !empty($assignedFloorIds);

            $query = DeliveryTask::withoutGlobalScope(TenantScope::class)
                ->whereIn('status', ['assigned', 'waiting_assignment']);
            
            // CRITICAL: Apply hotel_id filter for tenant isolation
            if ($hotelId) {
                $query->where('delivery_tasks.hotel_id', $hotelId);
            }
            
            if ($waiterId && (!$isAdminOrManager || $hasAssignments)) {
                $query->where(function($q) use ($waiterId, $assignedFloorIds, $assignedTableIds, $hasAssignments) {
                    $q->where('delivery_tasks.waiter_id', $waiterId);

                    if (!empty($assignedFloorIds)) {
                        $q->orWhere(function ($sub) use ($assignedFloorIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($f) use ($assignedFloorIds) {
                                $f->whereIn('delivery_tasks.floor_id', $assignedFloorIds)
                                  ->orWhereHas('order.room', fn($r) => $r->whereIn('floor_id', $assignedFloorIds));
                            });
                        });
                    }

                    if (!empty($assignedTableIds)) {
                        $q->orWhere(function ($sub) use ($assignedTableIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($t) use ($assignedTableIds) {
                                $t->whereIn('delivery_tasks.table_id', $assignedTableIds)
                                  ->orWhereHas('order', fn($o) => $o->whereIn('table_id', $assignedTableIds));
                            });
                        });
                    }

                    if (!$hasAssignments) {
                        $q->orWhereNull('delivery_tasks.waiter_id');
                    }
                });
            }
            return $query->count();
        } catch (\Throwable $e) {
            \Log::error('Pending count error: ' . $e->getMessage());
            return 0;
        }
    }

    public function getActiveCount($waiterId = null): int
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

            $query = DeliveryTask::withoutGlobalScope(TenantScope::class)
                ->whereIn('status', ['accepted', 'picked_up', 'on_delivery']);
            
            // CRITICAL: Apply hotel_id filter for tenant isolation
            if ($hotelId) {
                $query->where('delivery_tasks.hotel_id', $hotelId);
            }
            
            if (!$isAdminOrManager && $waiterId) {
                $query->where('delivery_tasks.waiter_id', $waiterId);
            }
            return $query->count();
        } catch (\Throwable $e) {
            \Log::error('Active count error: ' . $e->getMessage());
            return 0;
        }
    }

    public function getAllKitchenReadyOrders($waiterId = null, int $limit = 20): array
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
            $assignedFloorIds = $waiterId ? $this->getWaiterAssignedFloorIds($waiterId) : [];
            $assignedTableIds = $waiterId ? $this->getWaiterAssignedTableIds($waiterId) : [];
            $hasAssignments = !empty($assignedTableIds) || !empty($assignedFloorIds);

            $query = Order::withoutGlobalScope(TenantScope::class)
                ->where('status', 'ready');
            if ($hotelId) {
                $query->where('hotel_id', $hotelId);
            }

            if ($waiterId && (!$isAdminOrManager || $hasAssignments)) {
                $query->where(function ($q) use ($waiterId, $assignedFloorIds, $assignedTableIds, $hasAssignments) {
                    $q->whereHas('deliveryTasks', fn($dt) => $dt->where('waiter_id', $waiterId));
                    if (!empty($assignedFloorIds)) {
                        $q->orWhereHas('room', fn($rq) => $rq->whereIn('floor_id', $assignedFloorIds));
                    }
                    if (!empty($assignedTableIds)) {
                        $q->orWhereIn('table_id', $assignedTableIds);
                    }
                    if (!$hasAssignments) {
                        $q->orWhereDoesntHave('deliveryTasks', fn($dt) => $dt->whereNotNull('waiter_id'));
                    }
                });
            }

            return $query
                ->with([
                    'guest:id,first_name,last_name',
                    'room:id,room_number,floor_id',
                    'table:id,table_number,section',
                    'orderItems:id,order_id,menu_item_id,quantity,notes',
                    'orderItems.menuItem:id,name',
                ])
                ->orderBy('updated_at', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    $tableNumber = $order->table?->table_number ?? null;
                    $isTableOrder = !empty($tableNumber) || !empty($order->table_id) || in_array($order->order_type, ['dine_in', 'walk_in']);
                    $roomNumber = !$isTableOrder ? ($order->room?->room_number ?? null) : null;
                    $destination = $tableNumber ? "Table {$tableNumber}" : ($roomNumber ? "Room {$roomNumber}" : ($isTableOrder ? 'Table Order' : 'Room Service'));

                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_type' => $order->order_type ?? ($isTableOrder ? 'dine_in' : 'room_service'),
                        'is_table_order' => $isTableOrder,
                        'room_number' => $roomNumber,
                        'table_number' => $tableNumber,
                        'table_section' => $order->table?->section ?? null,
                        'destination' => $destination,
                        'guest_name' => ($order->guest ? $order->guest->first_name . ' ' . $order->guest->last_name : ($tableNumber ? "Table {$tableNumber} Guest" : 'Guest')),
                        'items' => $order->orderItems?->count() ?? 0,
                        'special_requests' => $order->notes ?? $order->special_requests ?? 'None',
                        'priority' => $order->priority ?? 'normal',
                        'prepared_by' => 'Kitchen',
                        'ready_at' => $order->updated_at?->format('Y-m-d H:i:s'),
                        'wait_time_minutes' => $order->updated_at ? $order->updated_at->diffInMinutes(now()) : 0,
                        'total' => $order->total,
                        'items_detail' => $order->orderItems?->map(fn ($item) => [
                            'name' => $item->menuItem?->name ?? 'Unknown Item',
                            'quantity' => $item->quantity,
                            'notes' => $item->notes ?? 'None',
                        ])->toArray() ?? [],
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            \Log::error('All kitchen ready orders error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    public function getReadyForPickup($waiterId = null, int $limit = 20): array
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
            $assignedFloorIds = $waiterId ? $this->getWaiterAssignedFloorIds($waiterId) : [];
            $assignedTableIds = $waiterId ? $this->getWaiterAssignedTableIds($waiterId) : [];
            $hasAssignments = !empty($assignedTableIds) || !empty($assignedFloorIds);

            $tasksQuery = DeliveryTask::withoutGlobalScope(TenantScope::class)
                ->whereIn('status', ['assigned', 'waiting_assignment', 'accepted']);

            if ($hotelId) {
                $tasksQuery->where(function ($q) use ($hotelId) {
                    $q->where('delivery_tasks.hotel_id', $hotelId)
                      ->orWhereNull('delivery_tasks.hotel_id');
                });
            }

            if ($waiterId && (!$isAdminOrManager || $hasAssignments)) {
                $tasksQuery->where(function ($q) use ($waiterId, $assignedFloorIds, $assignedTableIds, $hasAssignments) {
                    // 1. Explicitly assigned to this waiter
                    $q->where('delivery_tasks.waiter_id', $waiterId);

                    // 2. Unassigned or waiter-assigned tasks matching assigned tables
                    if (!empty($assignedTableIds)) {
                        $q->orWhere(function ($sub) use ($assignedTableIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($t) use ($assignedTableIds) {
                                $t->whereIn('delivery_tasks.table_id', $assignedTableIds)
                                  ->orWhereHas('order', fn($o) => $o->whereIn('table_id', $assignedTableIds));
                            });
                        });
                    }

                    // 3. Unassigned or waiter-assigned tasks matching assigned floors
                    if (!empty($assignedFloorIds)) {
                        $q->orWhere(function ($sub) use ($assignedFloorIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($f) use ($assignedFloorIds) {
                                $f->whereIn('delivery_tasks.floor_id', $assignedFloorIds)
                                  ->orWhereHas('order.room', fn($r) => $r->whereIn('floor_id', $assignedFloorIds));
                            });
                        });
                    }

                    // 4. Pool fallback ONLY if waiter has NO assigned tables and NO assigned floors
                    if (!$hasAssignments) {
                        $q->orWhereNull('delivery_tasks.waiter_id');
                    }
                });
            }

            $tasks = $tasksQuery->with([
                'order:id,order_number,room_id,table_id,order_type,guest_id,status,notes,special_requests,total,updated_at',
                'order.guest:id,first_name,last_name',
                'order.room:id,room_number',
                'order.table:id,table_number,section',
                'order.orderItems:id,order_id,menu_item_id,quantity,notes',
                'order.orderItems.menuItem:id,name',
                'floor:id,floor_number',
                'room:id,room_number',
                'table:id,table_number,section',
            ])
            ->whereHas('order', function ($q) {
                $q->whereIn('status', ['ready', 'pending', 'preparing']);
            })
            ->orderBy('assigned_at', 'desc')
            ->limit($limit)
            ->get();

            $results = [];
            foreach ($tasks as $task) {
                $order = $task->order;
                if (!$order) continue;

                $tableNumber = $task->table?->table_number ?? $order->table?->table_number ?? null;
                $tableSection = $task->table?->section ?? $order->table?->section ?? null;
                $isTableOrder = !empty($tableNumber) || !empty($task->table_id) || !empty($order->table_id) || in_array($order->order_type, ['dine_in', 'walk_in']);

                $roomNumber = !$isTableOrder 
                    ? ($task->room?->room_number ?? $order->room?->room_number ?? null)
                    : null;

                $destination = $tableNumber 
                    ? "Table {$tableNumber}" 
                    : ($roomNumber ? "Room {$roomNumber}" : ($isTableOrder ? 'Table Order' : 'Room Service'));

                $guestName = $order->guest ? trim($order->guest->first_name . ' ' . $order->guest->last_name) : ($tableNumber ? "Table {$tableNumber} Guest" : 'Guest');

                $results[] = [
                    'id' => $task->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number ?? 'ORD-' . substr($order->id, 0, 8),
                    'order_type' => $order->order_type ?? ($isTableOrder ? 'dine_in' : 'room_service'),
                    'is_table_order' => $isTableOrder,
                    'room_number' => $roomNumber,
                    'table_number' => $tableNumber,
                    'table_section' => $tableSection,
                    'destination' => $destination,
                    'table' => $task->table ? [
                        'id' => $task->table->id,
                        'table_number' => $task->table->table_number,
                        'section' => $task->table->section,
                    ] : ($order->table ? [
                        'id' => $order->table->id,
                        'table_number' => $order->table->table_number,
                        'section' => $order->table->section,
                    ] : null),
                    'guest_name' => $guestName,
                    'items' => $order->orderItems?->count() ?? 0,
                    'assigned_at' => ($task->assigned_at ?? $task->created_at ?? now())->format('Y-m-d H:i:s'),
                    'wait_time_minutes' => ($order->updated_at ?? now())->diffInMinutes(now()),
                    'order_status' => $order->status,
                    'delivery_task_status' => $task->status,
                    'items_detail' => $order->orderItems?->map(fn ($item) => [
                        'name' => $item->menuItem?->name ?? 'Unknown Item',
                        'quantity' => $item->quantity,
                        'notes' => $item->notes ?? 'None',
                    ])->toArray() ?? [],
                    'special_requests' => $order->notes ?? $order->special_requests ?? 'None',
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            \Log::error('Ready for pickup error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    public function getPendingPickupOrders($waiterId, int $limit = 20): array
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
            $assignedFloorIds = $waiterId ? $this->getWaiterAssignedFloorIds($waiterId) : [];
            $assignedTableIds = $waiterId ? $this->getWaiterAssignedTableIds($waiterId) : [];
            $hasAssignments = !empty($assignedTableIds) || !empty($assignedFloorIds);

            $query = DeliveryTask::withoutGlobalScope(TenantScope::class)
                ->whereIn('status', ['assigned', 'waiting_assignment']);

            if ($hotelId) {
                $query->where(function ($q) use ($hotelId) {
                    $q->where('delivery_tasks.hotel_id', $hotelId)
                      ->orWhereNull('delivery_tasks.hotel_id');
                });
            }

            if ($waiterId && (!$isAdminOrManager || $hasAssignments)) {
                $query->where(function ($q) use ($waiterId, $assignedFloorIds, $assignedTableIds, $hasAssignments) {
                    $q->where('delivery_tasks.waiter_id', $waiterId);

                    if (!empty($assignedFloorIds)) {
                        $q->orWhere(function ($sub) use ($assignedFloorIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($f) use ($assignedFloorIds) {
                                $f->whereIn('delivery_tasks.floor_id', $assignedFloorIds)
                                  ->orWhereHas('order.room', fn($r) => $r->whereIn('floor_id', $assignedFloorIds));
                            });
                        });
                    }

                    if (!empty($assignedTableIds)) {
                        $q->orWhere(function ($sub) use ($assignedTableIds, $waiterId) {
                            $sub->where(function ($w) use ($waiterId) {
                                $w->whereNull('delivery_tasks.waiter_id')
                                  ->orWhere('delivery_tasks.waiter_id', $waiterId);
                            })->where(function ($t) use ($assignedTableIds) {
                                $t->whereIn('delivery_tasks.table_id', $assignedTableIds)
                                  ->orWhereHas('order', fn($o) => $o->whereIn('table_id', $assignedTableIds));
                            });
                        });
                    }

                    if (!$hasAssignments) {
                        $q->orWhereNull('delivery_tasks.waiter_id');
                    }
                });
            }

            $assignments = $query
                ->with([
                    'order:id,order_number,room_id,table_id,order_type,guest_id,status,special_requests',
                    'order.guest:id,first_name,last_name',
                    'order.room:id,room_number',
                    'order.table:id,table_number,section',
                    'order.orderItems:id,order_id,menu_item_id,quantity,notes',
                    'order.orderItems.menuItem:id,name',
                    'table:id,table_number,section',
                    'assignedBy:id,first_name,last_name'
                ])
                ->whereHas('order', fn($q) => $q->whereIn('status', ['preparing', 'ready']))
                ->orderBy('assigned_at', 'asc')
                ->limit($limit)
                ->get();

            return $assignments->map(function ($assignment) {
                $tableNumber = $assignment->table?->table_number ?? $assignment->order?->table?->table_number ?? null;
                $isTableOrder = !empty($tableNumber) || !empty($assignment->table_id) || !empty($assignment->order?->table_id);
                $roomNumber = !$isTableOrder ? ($assignment->order?->room?->room_number ?? 'N/A') : null;
                $destination = $tableNumber ? "Table {$tableNumber}" : ($roomNumber && $roomNumber !== 'N/A' ? "Room {$roomNumber}" : ($isTableOrder ? 'Table Order' : 'Room Service'));

                return [
                    'id' => $assignment->id,
                    'order_id' => $assignment->order_id,
                    'order_number' => $assignment->order?->order_number ?? (is_numeric($assignment->order_id) ? 'ORD-' . $assignment->order_id : substr($assignment->id, 0, 8)),
                    'order_type' => $assignment->order?->order_type ?? ($isTableOrder ? 'dine_in' : 'room_service'),
                    'is_table_order' => $isTableOrder,
                    'room_number' => $roomNumber,
                    'table_number' => $tableNumber,
                    'destination' => $destination,
                    'guest_name' => ($assignment->order?->guest ? $assignment->order->guest->first_name . ' ' . $assignment->order->guest->last_name : ($tableNumber ? "Table {$tableNumber} Guest" : 'Guest')),
                    'items' => $assignment->order?->orderItems?->count() ?? 0,
                    'priority' => $assignment->order?->priority ?? 'normal',
                    'assigned_at' => $assignment->assigned_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
                    'order_status' => $assignment->order?->status,
                    'is_ready' => $assignment->order?->status === 'ready',
                    'assigned_by_name' => ($assignment->assignedBy ? $assignment->assignedBy->first_name . ' ' . $assignment->assignedBy->last_name : 'System'),
                    'items_detail' => $assignment->order?->orderItems?->map(fn ($item) => [
                        'name' => $item->menuItem?->name ?? 'Unknown Item',
                        'quantity' => $item->quantity,
                        'notes' => $item->notes ?? 'None',
                    ])->toArray() ?? [],
                    'special_requests' => $assignment->order?->special_requests ?? 'None',
                ];
            })->toArray();
        } catch (\Throwable $e) {
            \Log::error(' Pending pickup orders error', [
                'error' => $e->getMessage(),
                'waiter_id' => $waiterId,
            ]);
            return [];
        }
    }

    public function getOnDelivery($waiterId = null, int $limit = 50): array
    {
        try {
            $hotelId = app(TenantContext::class)->getHotelId();
            $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
            
            $baseQuery = DeliveryTask::withoutGlobalScope(TenantScope::class)
                ->whereIn('status', ['on_delivery', 'picked_up']);

            if ($hotelId) {
                $baseQuery->where(function($q) use ($hotelId) {
                    $q->where('delivery_tasks.hotel_id', $hotelId)
                      ->orWhereNull('delivery_tasks.hotel_id');
                });
            }

            if (!$isAdminOrManager && $waiterId) {
                $baseQuery->where(function($q) use ($waiterId) {
                    $q->where('delivery_tasks.waiter_id', $waiterId)
                      ->orWhere('delivery_tasks.waiter_id', auth()->id());
                });
            }

            $tasks = $baseQuery
                ->with([
                    'order:id,order_number,room_id,table_id,order_type,priority,special_requests',
                    'order.guest:id,first_name,last_name',
                    'order.room:id,room_number',
                    'order.table:id,table_number,section',
                    'table:id,table_number,section',
                ])
                ->select([
                    'id', 'order_id', 'room_id', 'table_id', 'status', 'picked_up_at', 'on_delivery_at', 'created_at'
                ])
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();
            
            return $tasks->map(function ($assignment) {
                $tableNumber = $assignment->table?->table_number ?? $assignment->order?->table?->table_number ?? null;
                $isTableOrder = !empty($tableNumber) || !empty($assignment->table_id) || !empty($assignment->order?->table_id);
                $roomNumber = !$isTableOrder ? ($assignment->order?->room?->room_number ?? null) : null;
                $destination = $tableNumber ? "Table {$tableNumber}" : ($roomNumber ? "Room {$roomNumber}" : ($isTableOrder ? 'Table Order' : 'Room Service'));

                return [
                    'id' => $assignment->id,
                    'order_id' => $assignment->order_id,
                    'order_number' => $assignment->order?->order_number,
                    'order_type' => $assignment->order?->order_type ?? ($isTableOrder ? 'dine_in' : 'room_service'),
                    'is_table_order' => $isTableOrder,
                    'room_number' => $roomNumber,
                    'table_number' => $tableNumber,
                    'destination' => $destination,
                    'guest_name' => ($assignment->order?->guest ? $assignment->order->guest->first_name . ' ' . $assignment->order->guest->last_name : ($tableNumber ? "Table {$tableNumber} Guest" : 'N/A')),
                    'priority' => $assignment->order?->priority ?? 'normal',
                    'picked_up_at' => $assignment->picked_up_at?->format('Y-m-d H:i:s'),
                    'on_delivery_at' => $assignment->on_delivery_at?->format('Y-m-d H:i:s'),
                    'delivery_time_minutes' => $assignment->picked_up_at?->diffInMinutes(now()) ?? 0,
                    'special_requests' => $assignment->order?->special_requests ?? 'None',
                    'status' => $assignment->status,
                    'order_status' => $assignment->status,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            \Log::error('On delivery error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    public function getCompletedDeliveries($waiterId, $limit = 10): array
    {
        try {
            $query =DeliveryTask::where('status', 'delivered');

            $userTasks = (clone $query)->whereIn('waiter_id', [$waiterId, auth()->id()]);
            if ($userTasks->exists()) {
                $query = $userTasks;
            }

            $results = $query
                ->with([
                    'room:id,room_number',
                    'order:id,order_number,guest_id,room_id,notes',
                    'order.guest:id,first_name,last_name',
                    'order.room:id,room_number',
                ])
                ->select([
                    'id', 'order_id', 'room_id', 'status', 'delivered_at', 
                    'remarks', 'assigned_at', 'picked_up_at', 'created_at', 'updated_at'
                ])
                ->orderBy('delivered_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($assignment) {
                    $orderNumber = $assignment->order?->order_number
                        ?? (is_numeric($assignment->order_id) ? 'ORD-' . $assignment->order_id : null)
                        ?? 'ORD-' . substr($assignment->id, 0, 8);

                    $roomNumber = $assignment->room?->room_number
                        ?? $assignment->order?->room?->room_number
                        ?? ($assignment->room_id ? $assignment->room_id : 'N/A');

                    $guest = $assignment->order?->guest;
                    $guestName = $guest 
                        ? trim($guest->first_name . ' ' . $guest->last_name) 
                        : ($roomNumber !== 'N/A' ? 'Guest Room ' . $roomNumber : 'Guest');

                    $remarks = $assignment->remarks ?? $assignment->order?->notes ?? 'None';

                    return [
                        'id' => $assignment->id,
                        'order_id' => $assignment->order_id,
                        'order_number' => $orderNumber,
                        'room_number' => $roomNumber,
                        'guest_name' => $guestName,
                        'delivered_at' => ($assignment->delivered_at ?? $assignment->updated_at ?? $assignment->created_at)?->format('Y-m-d H:i:s'),
                        'delivery_time_minutes' => $assignment->getDeliveryDurationMinutes(),
                        'remarks' => $remarks,
                        'status' => $assignment->status,
                        'order_status' => $assignment->status,
                    ];
                })
                ->toArray();
            
            return $results;
        } catch (\Throwable $e) {
            \Log::error('Completed deliveries error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    public function getFailedDeliveries($waiterId, $limit = 10): array
    {
        try {
            $today = Carbon::today();
            $results = DeliveryTask::where('waiter_id', $waiterId)
                ->where('status', 'cancelled')
                ->whereDate('cancelled_at', $today)
                ->with([
                    'order:id,order_number,guest_id,room_id',
                    'order.guest:id,first_name,last_name',
                    'order.room:id,room_number',
                    'assignedBy:id,first_name,last_name,email',
                ])
                ->orderBy('cancelled_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($assignment) {
                    try {
                        return [
                            'id' => $assignment->id,
                            'order_id' => $assignment->order_id,
                            'order_number' => $assignment->order?->order_number,
                            'room_number' => $assignment->order?->room?->room_number,
                            'guest_name' => ($assignment->order?->guest ? $assignment->order->guest->first_name . ' ' . $assignment->order->guest->last_name : 'N/A'),
                            'failed_at' => $assignment->cancelled_at?->format('Y-m-d H:i:s'),
                            'failure_reason' => $assignment->cancellation_reason ?? 'No reason provided',
                            'remarks' => $assignment->remarks ?? 'None',
                            'status' => $assignment->status,
                            'order_status' => $assignment->status,
                        ];
                    } catch (\Throwable $e) {
                        \Log::error('Error mapping failed delivery', [
                            'task_id' => $assignment->id,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                })
                ->toArray();
            
            return $results;
        } catch (\Throwable $e) {
            \Log::error('Failed deliveries error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getDeliveryTimeline($waiterId): array
    {
        try {
            $today = Carbon::today();
            $logs = DeliveryLog::where('waiter_id', $waiterId)
                ->whereDate('created_at', $today)
                ->with(['order'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'order_id' => $log->order_id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'timestamp' => $log->created_at,
                ])
                ->toArray();

            return $logs;
        } catch (\Throwable $e) {
            \Log::error('Delivery timeline error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    public function getWeeklyPerformanceData($waiterId): array
    {
        try {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = $weekStart->copy()->addDays(6);

            // Single query for all 7 days
            $performances = WaiterPerformance::where('waiter_id', $waiterId)
                ->whereBetween('metric_date', [$weekStart, $weekEnd])
                ->get()
                ->keyBy(fn($p) => $p->metric_date->format('Y-m-d'));

            $dailyData = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i);
                $dateKey = $date->format('Y-m-d');
                $performance = $performances->get($dateKey);

                $dailyData[] = [
                    'date' => $dateKey,
                    'day' => $date->format('l'),
                    'deliveries' => $performance?->deliveries_completed ?? 0,
                    'failed' => $performance?->deliveries_failed ?? 0,
                    'average_delivery_time' => $performance?->avg_delivery_time_minutes ?? 0,
                    'rating' => $performance?->rating ?? 0,
                ];
            }

            return $dailyData;
        } catch (\Throwable $e) {
            \Log::error('Weekly performance error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    public function getMonthlyPerformanceData($waiterId): array
    {
        try {
            $today = Carbon::today();
            $monthStart = $today->copy()->startOfMonth();
            $monthEnd = $today->copy()->endOfMonth();

            $performances = WaiterPerformance::where('waiter_id', $waiterId)
                ->whereBetween('metric_date', [$monthStart, $monthEnd])
                ->get()
                ->groupBy(fn ($performance) => $performance->metric_date->format('W'));

            $weeklyData = [];
            foreach ($performances as $week => $weekPerformances) {
                $weeklyData[] = [
                    'week' => "Week {$week}",
                    'deliveries' => $weekPerformances->sum('deliveries_completed'),
                    'failed' => $weekPerformances->sum('deliveries_failed'),
                    'average_delivery_time' => round($weekPerformances->avg('avg_delivery_time_minutes'), 2),
                    'rating' => round($weekPerformances->avg('rating'), 2),
                ];
            }

            return $weeklyData;
        } catch (\Throwable $e) {
            \Log::error('Monthly performance error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    private function calculateAverageMetric($collection, $field): float
    {
        if ($collection->isEmpty()) {
            return 0;
        }

        $avg = $collection->avg($field);
        return $avg ? round($avg, 2) : 0;
    }

    public function getPerformanceComparison($waiterId): array
    {
        try {
            $thisWeekStart = Carbon::now()->startOfWeek();
            $lastWeekStart = $thisWeekStart->copy()->subWeek();
            $lastWeekEnd = $lastWeekStart->copy()->endOfWeek();

            $thisWeek = WaiterPerformance::where('waiter_id', $waiterId)
                ->whereBetween('metric_date', [$thisWeekStart, Carbon::now()])
                ->get();
            $lastWeek = WaiterPerformance::where('waiter_id', $waiterId)
                ->whereBetween('metric_date', [$lastWeekStart, $lastWeekEnd])
                ->get();
            $thisWeekDeliveries = $thisWeek->sum('deliveries_completed');
            $lastWeekDeliveries = $lastWeek->sum('deliveries_completed');
            return [
                'this_week' => [
                    'deliveries' => $thisWeekDeliveries,
                    'failed' => $thisWeek->sum('deliveries_failed'),
                    'average_delivery_time' => round($thisWeek->avg('avg_delivery_time_minutes'), 2),
                    'rating' => round($thisWeek->avg('rating'), 2),
                ],
                'last_week' => [
                    'deliveries' => $lastWeekDeliveries,
                    'failed' => $lastWeek->sum('deliveries_failed'),
                    'average_delivery_time' => round($lastWeek->avg('avg_delivery_time_minutes'), 2),
                    'rating' => round($lastWeek->avg('rating'), 2),
                ],
                'growth' => [
                    'deliveries' => $lastWeekDeliveries > 0 
                        ? round((($thisWeekDeliveries - $lastWeekDeliveries) / $lastWeekDeliveries) * 100, 2)
                        : 0,
                ],
            ];
        } catch (\Throwable $e) {
            \Log::error('Performance comparison error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [
                'this_week' => ['deliveries' => 0, 'failed' => 0, 'average_delivery_time' => 0, 'rating' => 0],
                'last_week' => ['deliveries' => 0, 'failed' => 0, 'average_delivery_time' => 0, 'rating' => 0],
                'growth' => ['deliveries' => 0],
            ];
        }
    }

    public function getQuickStats($waiterId): array
    {
        try {
            $today = Carbon::today();
            
            $stats =DeliveryTask::where('waiter_id', $waiterId)
                ->whereDate('assigned_at', $today)
                ->selectRaw('
                    SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status IN ("accepted", "picked_up", "on_delivery") THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as failed
                ')
                ->first();

            return [
                'pending' => (int)($stats->pending ?? 0),
                'active' => (int)($stats->active ?? 0),
                'completed' => (int)($stats->completed ?? 0),
                'failed' => (int)($stats->failed ?? 0),
            ];
        } catch (\Throwable $e) {
            \Log::error(' Quick stats error', [
                'error' => $e->getMessage(),
                'waiter_id' => $waiterId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [
                'pending' => 0,
                'active' => 0,
                'completed' => 0,
                'failed' => 0,
            ];
        }
    }
}
