# Waiter Dashboard Optimization Plan

## Executive Summary

The Waiter Dashboard currently suffers from severe performance issues due to:
- **15-25 DB queries per dashboard load** from getDashboardStats()
- **Repeated calls to getWaiterAssignedFloorIds()** with no caching (called 6+ times per request)
- **N+1 query problems** in eager loading (especially in getRecentAssignments)
- **Loop-based queries** in getWeeklyPerformanceData() firing 7 separate queries
- **Full-table scans** in getPerformanceMetrics() with no scoping
- **Excessive logging** in hot paths adding latency
- **Error responses returning success:true** instead of proper error handling
- **No pagination** on large dataset endpoints

**Expected Performance Improvement:** 70-85% reduction in database queries and 60-80% reduction in response time.

---

## Implementation Plan

### 1. Add Caching Layer for Floor Assignments

**Problem:** `getWaiterAssignedFloorIds()` is called 6+ times per request (in getTodayStats, getPendingCount, getRecentAssignments, getReadyForPickup, getPendingPickupOrders, getAllKitchenReadyOrders) with no caching, triggering the same query repeatedly.

**Solution:** Implement 60-second cache with per-waiter key.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
public function getWaiterAssignedFloorIds($waiterId): array
{
    if (!$waiterId) {
        return [];
    }

    $cacheKey = "waiter_floor_ids:{$waiterId}";
    
    return Cache::remember($cacheKey, 60, function () use ($waiterId) {
        try {
            return WaiterFloorAssignment::where('waiter_id', $waiterId)
                ->where(function ($q) {
                    $q->where('is_active', true)
                      ->orWhere('status', 'active');
                })
                ->pluck('floor_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            \Log::warning('Error resolving waiter assigned floor IDs: ' . $e->getMessage());
            return [];
        }
    });
}
```

Add cache-invalidation method when floor assignments change:
```php
public function clearWaiterFloorCache($waiterId): void
{
    Cache::forget("waiter_floor_ids:{$waiterId}");
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan test --filter WaiterDashboardServiceTest
```

**Expected impact:** Reduces 6 queries per request to 1 (cached for 60s).

---

### 2. Refactor getDashboardStats() to Share Context

**Problem:** getDashboardStats() calls 5 separate methods, each re-resolving hotelId and floor IDs independently, resulting in 15-25 total queries.

**Solution:** Resolve context once at the top level and pass down to child methods. Create optimized batch method.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
public function getDashboardStats($waiterId = null): array
{
    try {
        $hotelId = app(TenantContext::class)->getHotelId();
        $assignedFloorIds = $this->getWaiterAssignedFloorIds($waiterId);
        $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

        return [
            'today_stats' => $this->getTodayStatsOptimized($waiterId, $hotelId, $assignedFloorIds, $isAdminOrManager),
            'performance' => $this->getPerformanceMetricsOptimized($waiterId, $hotelId),
            'recent_assignments' => $this->getRecentAssignmentsOptimized($waiterId, 8, $hotelId, $assignedFloorIds, $isAdminOrManager),
            'pending_count' => $this->getPendingCountOptimized($waiterId, $hotelId, $assignedFloorIds, $isAdminOrManager),
            'active_count' => $this->getActiveCountOptimized($waiterId, $hotelId, $isAdminOrManager),
        ];
    } catch (\Throwable $e) {
        \Log::error('Dashboard stats error: ' . $e->getMessage());
        return [
            'today_stats' => $this->getDefaultTodayStats(),
            'performance' => $this->getDefaultPerformanceMetrics(),
            'recent_assignments' => [],
            'pending_count' => 0,
            'active_count' => 0,
        ];
    }
}
```

Keep original public methods for backward compatibility but make them call the optimized versions:
```php
public function getTodayStats($waiterId = null): array
{
    $hotelId = app(TenantContext::class)->getHotelId();
    $assignedFloorIds = $this->getWaiterAssignedFloorIds($waiterId);
    $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
    return $this->getTodayStatsOptimized($waiterId, $hotelId, $assignedFloorIds, $isAdminOrManager);
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan test --filter WaiterDashboardTest
```

**Expected impact:** Eliminates 5-10 redundant context resolution queries.

---

### 3. Optimize getTodayStats() with Single Aggregation Query

**Problem:** getTodayStats() clones the base query 3 times and fires a separate Order query, plus another getWaiterAssignedFloorIds() call.

**Solution:** Use a single aggregated query with all stats, pass cached floor IDs as parameter.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
private function getTodayStatsOptimized($waiterId, $hotelId, array $assignedFloorIds, bool $isAdminOrManager): array
{
    try {
        $today = Carbon::today();

        // Single aggregated query for all today's stats
        $taskQuery = DeliveryTask::withoutGlobalScope(TenantScope::class);

        if ($hotelId) {
            $taskQuery->where(function ($q) use ($hotelId) {
                $q->where('delivery_tasks.hotel_id', $hotelId)
                  ->orWhereNull('delivery_tasks.hotel_id');
            });
        }

        if (!$isAdminOrManager && $waiterId) {
            $taskQuery->where(function($q) use ($waiterId) {
                $q->where('delivery_tasks.waiter_id', $waiterId)
                  ->orWhere('delivery_tasks.waiter_id', auth()->id());
            });
        }

        // Get both historical (today) and current stats in one query
        $allStats = $taskQuery->selectRaw('
            -- Today historical
            SUM(CASE WHEN DATE(COALESCE(delivered_at, assigned_at, created_at)) = CURDATE() THEN 1 ELSE 0 END) as total_assignments,
            SUM(CASE WHEN status = "delivered" AND DATE(delivered_at) = CURDATE() THEN 1 ELSE 0 END) as completed_deliveries,
            SUM(CASE WHEN status = "cancelled" AND DATE(COALESCE(cancelled_at, created_at)) = CURDATE() THEN 1 ELSE 0 END) as failed_deliveries,
            -- Current active
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

        // Get kitchen ready count (orders not picked up yet)
        $pickedUpOrderIds = DeliveryTask::withoutGlobalScope(TenantScope::class)
            ->whereIn('status', ['picked_up', 'on_delivery', 'delivered', 'cancelled'])
            ->pluck('order_id')
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
        if (!$isAdminOrManager && $waiterId && !empty($assignedFloorIds)) {
            $orderReadyQuery->whereHas('room', fn($rq) => $rq->whereIn('floor_id', $assignedFloorIds));
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
        \Log::error('Today stats error: ' . $e->getMessage());
        return $this->getDefaultTodayStats();
    }
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan tinker
# Test: app(App\Services\Waiter\WaiterDashboardService::class)->getTodayStats(1)
```

**Expected impact:** Reduces 4-5 queries to 2 queries.

---

### 4. Optimize getPerformanceMetrics() - Eliminate Inner Closure Queries

**Problem:** getPerformanceMetrics() fires 3 WaiterPerformance queries + a closure that fires 3 DeliveryTask queries each for today/week/month = 9 extra queries. Also fires 2 full-table DeliveryTask counts with no scoping.

**Solution:** Replace the inner closure's 3-query pattern with a single selectRaw per time window. Remove full-table fallbacks.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
private function getPerformanceMetricsOptimized($waiterId, $hotelId): array
{
    try {
        $now = Carbon::now();
        $today = Carbon::today();
        
        $userIds = [$waiterId];
        if (auth()->check()) {
            $userIds[] = auth()->id();
        }
        $waiterModel = Waiter::find($waiterId);
        if ($waiterModel) {
            $userIds[] = $waiterModel->user_id;
        }
        $userIds = array_values(array_unique(array_filter($userIds)));

        // Get WaiterPerformance records
        $todayPerformance = WaiterPerformance::whereIn('waiter_id', $userIds)
            ->where('metric_date', $today)
            ->first();

        $weekPerformance = WaiterPerformance::whereIn('waiter_id', $userIds)
            ->where('metric_date', '>=', $now->copy()->subDays(7)->startOfDay())
            ->get();

        $monthPerformance = WaiterPerformance::whereIn('waiter_id', $userIds)
            ->where('metric_date', '>=', $now->copy()->subDays(30)->startOfDay())
            ->get();

        // Single aggregated query for all time windows
        $taskMetrics = DeliveryTask::whereIn('waiter_id', $userIds)
            ->selectRaw('
                -- Today
                SUM(CASE WHEN status = "delivered" AND DATE(delivered_at) = CURDATE() THEN 1 ELSE 0 END) as today_completed,
                SUM(CASE WHEN status IN ("failed", "cancelled") AND DATE(COALESCE(cancelled_at, created_at)) = CURDATE() THEN 1 ELSE 0 END) as today_failed,
                AVG(CASE WHEN status = "delivered" AND DATE(delivered_at) = CURDATE() AND TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) > 0 
                    THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) 
                    ELSE NULL 
                END) as today_avg_time,
                -- Week (last 7 days)
                SUM(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_completed,
                SUM(CASE WHEN status IN ("failed", "cancelled") AND COALESCE(cancelled_at, created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_failed,
                AVG(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) > 0 
                    THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) 
                    ELSE NULL 
                END) as week_avg_time,
                -- Month (last 30 days)
                SUM(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_completed,
                SUM(CASE WHEN status IN ("failed", "cancelled") AND COALESCE(cancelled_at, created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_failed,
                AVG(CASE WHEN status = "delivered" AND delivered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) > 0 
                    THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) 
                    ELSE NULL 
                END) as month_avg_time
            ')
            ->first();

        return [
            'today' => [
                'deliveries' => max($todayPerformance?->deliveries_completed ?? 0, $taskMetrics->today_completed ?? 0),
                'failed' => max($todayPerformance?->deliveries_failed ?? 0, $taskMetrics->today_failed ?? 0),
                'average_delivery_time' => $todayPerformance?->avg_delivery_time_minutes ?? round($taskMetrics->today_avg_time ?? 15, 1),
                'rating' => $todayPerformance?->rating ?? 4.8,
                'guest_rating' => $todayPerformance?->guest_rating_avg ?? 4.8,
                'success_rate' => $this->calculateSuccessRate(
                    max($todayPerformance?->deliveries_completed ?? 0, $taskMetrics->today_completed ?? 0),
                    max($todayPerformance?->deliveries_failed ?? 0, $taskMetrics->today_failed ?? 0)
                ),
            ],
            'week' => [
                'deliveries' => max($weekPerformance->sum('deliveries_completed'), $taskMetrics->week_completed ?? 0),
                'failed' => max($weekPerformance->sum('deliveries_failed'), $taskMetrics->week_failed ?? 0),
                'average_delivery_time' => $this->calculateAverageMetric($weekPerformance, 'avg_delivery_time_minutes') ?: round($taskMetrics->week_avg_time ?? 18, 1),
                'rating' => $this->calculateAverageMetric($weekPerformance, 'rating') ?: 4.8,
                'guest_rating' => $this->calculateAverageMetric($weekPerformance, 'guest_rating_avg') ?: 4.8,
                'success_rate' => $this->calculateSuccessRate(
                    max($weekPerformance->sum('deliveries_completed'), $taskMetrics->week_completed ?? 0),
                    max($weekPerformance->sum('deliveries_failed'), $taskMetrics->week_failed ?? 0)
                ),
            ],
            'month' => [
                'deliveries' => max($monthPerformance->sum('deliveries_completed'), $taskMetrics->month_completed ?? 0),
                'failed' => max($monthPerformance->sum('deliveries_failed'), $taskMetrics->month_failed ?? 0),
                'average_delivery_time' => $this->calculateAverageMetric($monthPerformance, 'avg_delivery_time_minutes') ?: round($taskMetrics->month_avg_time ?? 16, 1),
                'rating' => $this->calculateAverageMetric($monthPerformance, 'rating') ?: 4.8,
                'guest_rating' => $this->calculateAverageMetric($monthPerformance, 'guest_rating_avg') ?: 4.8,
                'success_rate' => $this->calculateSuccessRate(
                    max($monthPerformance->sum('deliveries_completed'), $taskMetrics->month_completed ?? 0),
                    max($monthPerformance->sum('deliveries_failed'), $taskMetrics->month_failed ?? 0)
                ),
            ],
        ];
    } catch (\Throwable $e) {
        \Log::error('Performance metrics error: ' . $e->getMessage());
        return $this->getDefaultPerformanceMetrics();
    }
}

private function calculateSuccessRate(int $completed, int $failed): float
{
    $total = $completed + $failed;
    return $total > 0 ? round(($completed / $total) * 100, 1) : 100.0;
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan tinker
# Test: app(App\Services\Waiter\WaiterDashboardService::class)->getPerformanceMetrics(1)
```

**Expected impact:** Reduces 12 queries to 4 queries.

---

### 5. Fix N+1 in getRecentAssignments() Eager Loading

**Problem:** getRecentAssignments() eager-loads 7 relationships including redundant paths (order.reservation.guest when order.guest exists, order.reservation.room when order.room exists), causing N+1 on the order.reservation.guest chain.

**Solution:** Drop redundant paths, select only needed columns in each with() constraint.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
private function getRecentAssignmentsOptimized($waiterId, $limit, $hotelId, array $assignedFloorIds, bool $isAdminOrManager): array
{
    try {
        $baseQuery = DeliveryTask::withoutGlobalScope(TenantScope::class);
        
        if ($hotelId) {
            $baseQuery->where(function ($q) use ($hotelId) {
                $q->where('delivery_tasks.hotel_id', $hotelId)
                  ->orWhereNull('delivery_tasks.hotel_id');
            });
        }

        if (!$isAdminOrManager && $waiterId) {
            $baseQuery->where(function ($q) use ($waiterId, $assignedFloorIds) {
                $q->where('delivery_tasks.waiter_id', $waiterId);
                if (!empty($assignedFloorIds)) {
                    $q->orWhere(function ($sub) use ($assignedFloorIds) {
                        $sub->whereNull('delivery_tasks.waiter_id')
                            ->whereIn('delivery_tasks.floor_id', $assignedFloorIds)
                            ->where('delivery_tasks.status', 'waiting_assignment');
                    });
                }
            });
        }

        $deliveryTasks = $baseQuery
            ->with([
                'room:id,room_number,floor_id',
                'floor:id,floor_number',
                'order:id,order_number,guest_id,room_id',
                'order.guest:id,first_name,last_name',
                'order.room:id,room_number',
                'order.orderItems:id,order_id,quantity'
            ])
            ->select([
                'id', 'order_id', 'room_id', 'floor_id', 'status', 
                'assignment_type', 'assigned_at', 'accepted_at', 'picked_up_at', 
                'on_delivery_at', 'delivered_at', 'remarks', 'created_at'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($deliveryTasks->isEmpty()) {
            return [];
        }

        return $deliveryTasks->map(function ($delivery) {
            $orderNumber = $delivery->order?->order_number
                ?? (is_numeric($delivery->order_id) ? 'ORD-' . $delivery->order_id : null)
                ?? 'ORD-' . substr($delivery->id, 0, 8);

            $roomNumber = $delivery->room?->room_number
                ?? $delivery->order?->room?->room_number
                ?? ($delivery->room_id ? $delivery->room_id : 'N/A');

            $guest = $delivery->order?->guest;
            $guestName = $guest ? trim($guest->first_name . ' ' . $guest->last_name) : ($roomNumber !== 'N/A' ? 'Guest Room ' . $roomNumber : 'Guest');

            $itemCount = $delivery->order?->orderItems?->count() ?? 1;

            return [
                'id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'room_id' => $delivery->room_id,
                'room_number' => $roomNumber,
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
        })->toArray();
    } catch (\Throwable $e) {
        \Log::error('Recent assignments error: ' . $e->getMessage());
        return [];
    }
}

public function getRecentAssignments($waiterId = null, $limit = 10): array
{
    $hotelId = app(TenantContext::class)->getHotelId();
    $assignedFloorIds = $this->getWaiterAssignedFloorIds($waiterId);
    $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));
    return $this->getRecentAssignmentsOptimized($waiterId, $limit, $hotelId, $assignedFloorIds, $isAdminOrManager);
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan tinker
# Test: app(App\Services\Waiter\WaiterDashboardService::class)->getRecentAssignments(1, 10)
```

**Expected impact:** Reduces N+1 queries, eliminates 2 redundant relationship loads per row.

---

### 6. Fix getWeeklyPerformanceData() Loop-Based Queries

**Problem:** getWeeklyPerformanceData() fires a separate WaiterPerformance query inside a for-loop (7 queries instead of 1).

**Solution:** Fetch all 7 days in one query and group in PHP.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
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
        \Log::error('Weekly performance error: ' . $e->getMessage());
        return [];
    }
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan tinker
# Test: app(App\Services\Waiter\WaiterDashboardService::class)->getWeeklyPerformanceData(1)
```

**Expected impact:** Reduces 7 queries to 1 query.

---

### 7. Add Pagination/Limits to Large Dataset Endpoints

**Problem:** getAllKitchenReadyOrders(), getReadyForPickup(), getOnDelivery() have no pagination/limit — can return hundreds of rows.

**Solution:** Add default limit of 50, make it configurable via request parameter.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`
- `d:\Restaurant_system2\server\app\Http\Controllers\Api\Waiter\WaiterDashboardController.php`

**Changes in Service:**
```php
public function getAllKitchenReadyOrders($waiterId = null, int $limit = 50): array
{
    // Add ->limit($limit) before ->get()
    return $query
        ->with([...])
        ->orderBy('updated_at', 'asc')
        ->limit($limit)
        ->get()
        ->map(...)
        ->toArray();
}

public function getReadyForPickup($waiterId = null, int $limit = 50): array
{
    // Add ->limit($limit) before ->get()
    $tasks = $tasksQuery->with([...])
        ->whereHas('order', ...)
        ->orderBy('assigned_at', 'desc')
        ->limit($limit)
        ->get();
    // rest unchanged
}

public function getOnDelivery($waiterId = null, int $limit = 50): array
{
    // Add ->limit($limit) before ->get()
    $tasks = $baseQuery
        ->with(...)
        ->orderBy('created_at', 'asc')
        ->limit($limit)
        ->get();
    // rest unchanged
}
```

**Changes in Controller:**
```php
public function getKitchenReadyOrders(): JsonResponse
{
    return $this->handleAction(
        fn($userId) => $this->dashboardService->getAllKitchenReadyOrders($userId, request()->query('limit', 50)),
    );
}

public function getReadyForPickup(): JsonResponse
{
    return $this->handleAction(
        fn($userId) => $this->dashboardService->getReadyForPickup($userId, request()->query('limit', 50)),
    );
}

public function getOnDelivery(): JsonResponse
{
    return $this->handleAction(
        fn($waiterId) => $this->dashboardService->getOnDelivery($waiterId, request()->query('limit', 50)),
    );
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan test --filter WaiterDashboardControllerTest
```

**Expected impact:** Prevents unbounded result sets, reduces memory usage and response time.

---

### 8. Fix Error Response Handling in Controller

**Problem:** handleAction() catch block returns success:true on exceptions — swallows real errors. getDashboard() catch block also returns success:true.

**Solution:** Return proper error responses with success:false.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Http\Controllers\Api\Waiter\WaiterDashboardController.php`

**Changes:**
```php
private function handleAction(callable $action, array $defaultData = []): JsonResponse
{
    try {
        $request = request();
        $this->resolveTenant($request);
        $waiterId = $this->getWaiterId();

        $result = $action($waiterId);

        return response()->json([
            'success' => true,
            'data' => $result ?? $defaultData,
        ], 200);
    } catch (\Throwable $e) {
        \Log::error('Dashboard action error:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch dashboard data',
            'data' => $defaultData,
        ], 500);
    }
}

public function getDashboard(Request $request): JsonResponse
{
    try {
        $hotelId = $this->resolveTenant($request);
        $waiterId = $this->getWaiterId();

        $result = $this->dashboardService->getDashboardStats($waiterId);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    } catch (\Throwable $e) {
        \Log::error('Dashboard action error:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch dashboard data',
            'data' => [
                'today_stats' => [],
                'performance' => [],
                'recent_assignments' => [],
                'pending_count' => 0,
                'active_count' => 0,
            ],
        ], 500);
    }
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan test --filter WaiterDashboardControllerTest::test_error_responses
```

**Expected impact:** Frontend can properly handle errors instead of false positives.

---

### 9. Remove Excessive Logging from Hot Paths

**Problem:** All \Log::info() statements inside hot paths (getDashboardStats, getTodayStats, getPerformanceMetrics, getRecentAssignments, getReadyForPickup, getQuickStats) add latency and noise.

**Solution:** Remove or wrap in environment check.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`
- `d:\Restaurant_system2\server\app\Http\Controllers\Api\Waiter\WaiterDashboardController.php`

**Changes:**
Replace all `\Log::info()` calls in hot paths with:
```php
if (config('app.debug') && config('app.env') === 'local') {
    \Log::debug('[SERVICE] message', $context);
}
```

Or simply remove them. Keep only \Log::error() and \Log::warning() calls.

**Verification:**
```bash
cd d:\Restaurant_system2\server
# Manual test: check logs after dashboard load
```

**Expected impact:** Reduces I/O overhead, speeds up response time by 5-10%.

---

### 10. Optimize getCompletedDeliveries() Eager Loading

**Problem:** getCompletedDeliveries() eager-loads order.reservation.room and order.reservation.guest unnecessarily (already has order.guest and order.room direct paths).

**Solution:** Drop redundant reservation paths.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
public function getCompletedDeliveries($waiterId, $limit = 10): array
{
    try {
        $query = DeliveryTask::where('status', 'delivered');

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
        \Log::error('Completed deliveries error: ' . $e->getMessage());
        return [];
    }
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan tinker
# Test: app(App\Services\Waiter\WaiterDashboardService::class)->getCompletedDeliveries(1, 10)
```

**Expected impact:** Eliminates 2 unnecessary relationship loads per row.

---

### 11. Optimize getOnDelivery() - Remove Unused Eager Load

**Problem:** getOnDelivery() eager-loads 'assignedBy' but never uses its fields in the mapped response.

**Solution:** Remove 'assignedBy' from eager loading.

**Files to modify:**
- `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php`

**Changes:**
```php
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
                'order:id,order_number,priority,special_requests',
                'order.guest:id,first_name,last_name',
                'order.room:id,room_number',
            ])
            ->select([
                'id', 'order_id', 'status', 'picked_up_at', 'on_delivery_at', 'created_at'
            ])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
        
        return $tasks->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'order_number' => $assignment->order?->order_number,
                'room_number' => $assignment->order?->room?->room_number,
                'guest_name' => ($assignment->order?->guest ? $assignment->order->guest->first_name . ' ' . $assignment->order->guest->last_name : 'N/A'),
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
        \Log::error('On delivery error: ' . $e->getMessage());
        return [];
    }
}
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan tinker
# Test: app(App\Services\Waiter\WaiterDashboardService::class)->getOnDelivery(1)
```

**Expected impact:** Eliminates 1 unnecessary relationship load per row.

---

### 12. Create Database Index Migration

**Problem:** Some queries need composite indexes for optimal performance.

**Solution:** Create migration to add missing composite indexes.

**Files to create:**
- `d:\Restaurant_system2\server\database\migrations\2027_01_06_000001_add_waiter_dashboard_indexes.php`

**Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds composite indexes for Waiter Dashboard optimization
     * Expected performance improvement: 30-50% on filtered queries
     */
    public function up(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            // Composite index for waiter + status + assigned_at (getTodayStats, getQuickStats)
            if (!$this->indexExists('delivery_tasks', 'delivery_tasks_waiter_status_assigned_idx')) {
                $table->index(['waiter_id', 'status', 'assigned_at'], 'delivery_tasks_waiter_status_assigned_idx');
            }

            // Composite index for hotel + status + created_at (dashboard filtering)
            if (!$this->indexExists('delivery_tasks', 'delivery_tasks_hotel_status_created_idx')) {
                $table->index(['hotel_id', 'status', 'created_at'], 'delivery_tasks_hotel_status_created_idx');
            }

            // Composite index for order_id + status (order-based lookups)
            if (!$this->indexExists('delivery_tasks', 'delivery_tasks_order_status_idx')) {
                $table->index(['order_id', 'status'], 'delivery_tasks_order_status_idx');
            }
        });

        Schema::table('waiter_performance', function (Blueprint $table) {
            // Composite index for waiter + metric_date (performance queries)
            if (!$this->indexExists('waiter_performance', 'waiter_performance_waiter_date_idx')) {
                $table->index(['waiter_id', 'metric_date'], 'waiter_performance_waiter_date_idx');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // Composite index for hotel + status + updated_at (kitchen ready orders)
            if (!$this->indexExists('orders', 'orders_hotel_status_updated_idx')) {
                $table->index(['hotel_id', 'status', 'updated_at'], 'orders_hotel_status_updated_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropIndexIfExists('delivery_tasks_waiter_status_assigned_idx');
            $table->dropIndexIfExists('delivery_tasks_hotel_status_created_idx');
            $table->dropIndexIfExists('delivery_tasks_order_status_idx');
        });

        Schema::table('waiter_performance', function (Blueprint $table) {
            $table->dropIndexIfExists('waiter_performance_waiter_date_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndexIfExists('orders_hotel_status_updated_idx');
        });
    }

    /**
     * Check if index exists (helper function)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $indexes = $connection->select(
            "SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$databaseName, $table, $indexName]
        );
        return count($indexes) > 0;
    }
};
```

**Verification:**
```bash
cd d:\Restaurant_system2\server
php artisan migrate
php artisan migrate:rollback --step=1
php artisan migrate
```

**Expected impact:** 30-50% improvement on filtered queries with composite conditions.

---

## Summary of Changes

### Files Changed
1. `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php` (major refactor)
2. `d:\Restaurant_system2\server\app\Http\Controllers\Api\Waiter\WaiterDashboardController.php` (error handling + pagination)
3. `d:\Restaurant_system2\server\database\migrations\2027_01_06_000001_add_waiter_dashboard_indexes.php` (new)

### Queries Removed/Combined
- **Before:** 15-25 queries per getDashboardStats()
- **After:** 6-8 queries per getDashboardStats()
- **Reduction:** 60-70%

**Specific reductions:**
- getWaiterAssignedFloorIds: 6 calls → 1 call (cached)
- getTodayStats: 4-5 queries → 2 queries
- getPerformanceMetrics: 12 queries → 4 queries
- getWeeklyPerformanceData: 7 queries → 1 query
- getRecentAssignments: N+1 eliminated, 2 redundant loads removed per row
- getCompletedDeliveries: 2 redundant loads removed per row
- getOnDelivery: 1 redundant load removed per row

### N+1 Problems Fixed
1.  getRecentAssignments(): order.reservation.guest N+1 eliminated
2.  getRecentAssignments(): Redundant reservation paths removed
3.  getCompletedDeliveries(): Redundant reservation paths removed
4.  getOnDelivery(): Unused assignedBy eager load removed

### Caching Added
-  getWaiterAssignedFloorIds(): 60-second cache with per-waiter key
-  Cache uses Laravel's default CACHE_STORE (database) - no Redis dependency required
-  Cache invalidation method provided: clearWaiterFloorCache()

### Indexes Added
-  delivery_tasks(waiter_id, status, assigned_at)
-  delivery_tasks(hotel_id, status, created_at)
-  delivery_tasks(order_id, status)
-  waiter_performance(waiter_id, metric_date)
-  orders(hotel_id, status, updated_at)

### Pagination Added
-  getAllKitchenReadyOrders(): default limit 50
-  getReadyForPickup(): default limit 50
-  getOnDelivery(): default limit 50

### Error Handling Fixed
-  handleAction(): now returns success:false on exceptions
-  getDashboard(): now returns success:false on exceptions

### Logging Optimized
-  Removed excessive \Log::info() from hot paths
-  Kept \Log::error() and \Log::warning() for actual issues

---

## Expected Performance Improvement

### Database Query Reduction
- **getDashboardStats():** 15-25 queries → 6-8 queries (60-70% reduction)
- **Overall dashboard load:** 70-85% fewer queries

### Response Time Improvement
- **Before:** 800-1500ms (estimated based on query count)
- **After:** 200-400ms (estimated)
- **Improvement:** 60-80% faster

### Memory Usage
- **Before:** Unbounded result sets could load 100+ rows
- **After:** Capped at 50 rows per endpoint (configurable)
- **Improvement:** 50-80% reduction for large datasets

### Cache Hit Rate
- **Floor assignments:** 95%+ hit rate (changes infrequently)
- **Effective query reduction from caching alone:** 5-6 queries per request

---

## Frontend Changes Required

### None - Response Structure Unchanged

All optimizations maintain 100% backward compatibility with existing frontend:
-  Same JSON response structure
-  Same field names and types
-  Same endpoint URLs
-  Only internal query optimization

**Exception:** Error responses now properly return `success: false` instead of `success: true`. Frontend should already handle this correctly, but verify error handling flows.

---

## Testing Checklist

1.  Run full test suite: `php artisan test`
2.  Test each endpoint manually via Postman/Thunder Client
3.  Verify dashboard loads in under 500ms
4.  Check error responses return success:false
5.  Confirm pagination works with ?limit=20
6.  Validate cache invalidation on floor assignment changes
7.  Monitor logs for remaining excessive logging
8.  Run migration and verify indexes created
9.  Load test with 10+ concurrent users
10.  Verify multi-tenant isolation still works

---

## Rollback Plan

If issues arise:
1. Git revert the service changes
2. Git revert the controller changes
3. Rollback migration: `php artisan migrate:rollback --step=1`
4. Clear cache: `php artisan cache:clear`

Each change is isolated and can be rolled back independently.

---

## Notes

- **Cache driver:** Currently using 'database' cache (from .env CACHE_STORE=database). No Redis/Predis packages detected in composer.json. The plan uses Laravel's Cache facade which works with any driver.
- **Laravel version:** 13.17.0 (Laravel 13.x)
- **Existing indexes:** delivery_tasks already has single-column indexes on waiter_id, floor_id, status, order_id from previous migrations. The new migration adds composite indexes for multi-column filtering.
- **Multi-tenant safety:** All optimizations preserve hotel_id filtering and TenantScope behavior.
- **Authentication:** All optimizations preserve waiter authentication and WaiterContextResolver logic.
