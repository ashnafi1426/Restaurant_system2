<?php

namespace App\Services\Waiter;

use App\Models\DeliveryLog;
use App\Models\WaiterPerformance;
use Carbon\Carbon;

class WaiterDashboardService
{
    public function getDashboardStats($waiterId): array
    {
        try {
            if (!$waiterId) {
                \Log::warning('❌ getDashboardStats called with empty waiterId');
                return [
                    'today_stats' => $this->getDefaultTodayStats(),
                    'performance' => $this->getDefaultPerformanceMetrics(),
                    'recent_assignments' => [],
                    'pending_count' => 0,
                    'active_count' => 0,
                ];
            }

            \Log::info('🔵 [SERVICE] getDashboardStats called:', [
                'waiter_id' => $waiterId,
            ]);

            $result = [
                'today_stats' => $this->getTodayStats($waiterId),
                'performance' => $this->getPerformanceMetrics($waiterId),
                'recent_assignments' => $this->getRecentAssignments($waiterId),
                'pending_count' => $this->getPendingCount($waiterId),
                'active_count' => $this->getActiveCount($waiterId),
            ];

            \Log::info('✅ [SERVICE] getDashboardStats result:', [
                'waiter_id' => $waiterId,
                'result' => $result,
            ]);

            return $result;
        } catch (\Throwable $e) {
            \Log::error('❌ Dashboard stats error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [
                'today_stats' => $this->getDefaultTodayStats(),
                'performance' => $this->getDefaultPerformanceMetrics(),
                'recent_assignments' => [],
                'pending_count' => 0,
                'active_count' => 0,
            ];
        }
    }
    public function getTodayStats($waiterId): array
    {
        try {
            if (!$waiterId) {
                \Log::warning('getTodayStats called with empty waiterId');
                return $this->getDefaultTodayStats();
            }

            $today = Carbon::today();
            \Log::info('🔵 [SERVICE] getTodayStats querying:', [
                'waiter_id' => $waiterId,
                'date' => $today->toDateString(),
            ]);

            $stats = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->whereDate('assigned_at', $today)
                ->selectRaw('
                    COUNT(*) as total_assignments,
                    SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed_deliveries,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as failed_deliveries,
                    SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as pending_assignments,
                    SUM(CASE WHEN status IN ("accepted", "picked_up", "on_delivery") THEN 1 ELSE 0 END) as active_assignments,
                    SUM(CASE WHEN status = "on_delivery" THEN 1 ELSE 0 END) as on_delivery_count,
                    ROUND(AVG(CASE WHEN status = "delivered" THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) ELSE NULL END), 2) as average_delivery_time
                ')
                ->first();

            \Log::info('✅ [SERVICE] getTodayStats result:', [
                'waiter_id' => $waiterId,
                'stats' => $stats ? $stats->toArray() : null,
            ]);

            $completionRate = $stats->total_assignments > 0 
                ? round(($stats->completed_deliveries / $stats->total_assignments) * 100, 2) 
                : 0;

            return [
                'total_assignments' => (int)($stats->total_assignments ?? 0),
                'completed_deliveries' => (int)($stats->completed_deliveries ?? 0),
                'failed_deliveries' => (int)($stats->failed_deliveries ?? 0),
                'rejected_assignments' => 0,
                'pending_assignments' => (int)($stats->pending_assignments ?? 0),
                'active_assignments' => (int)($stats->active_assignments ?? 0),
                'on_delivery_count' => (int)($stats->on_delivery_count ?? 0),
                'average_delivery_time' => (float)($stats->average_delivery_time ?? 0),
                'completion_rate' => $completionRate,
            ];
        } catch (\Throwable $e) {
            \Log::error('❌ Today stats error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
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
            $today = Carbon::today();
            $todayPerformance = WaiterPerformance::where('waiter_id', $waiterId)
                ->where('metric_date', $today)
                ->first();

            $weekStart = Carbon::now()->startOfWeek();
            $weekPerformance = WaiterPerformance::where('waiter_id', $waiterId)
                ->whereBetween('metric_date', [$weekStart, $today])
                ->get();

            $monthStart = Carbon::now()->startOfMonth();
            $monthPerformance = WaiterPerformance::where('waiter_id', $waiterId)
                ->whereBetween('metric_date', [$monthStart, $today])
                ->get();

            return [
                'today' => [
                    'deliveries' => $todayPerformance?->deliveries_completed ?? 0,
                    'failed' => $todayPerformance?->deliveries_failed ?? 0,
                    'average_delivery_time' => $todayPerformance?->avg_delivery_time_minutes ?? 0,
                    'rating' => $todayPerformance?->rating ?? 0,
                    'guest_rating' => $todayPerformance?->guest_rating_avg ?? 0,
                ],
                'week' => [
                    'deliveries' => $weekPerformance->sum('deliveries_completed'),
                    'failed' => $weekPerformance->sum('deliveries_failed'),
                    'average_delivery_time' => $this->calculateAverageMetric($weekPerformance, 'avg_delivery_time_minutes'),
                    'rating' => $this->calculateAverageMetric($weekPerformance, 'rating'),
                    'guest_rating' => $this->calculateAverageMetric($weekPerformance, 'guest_rating_avg'),
                ],
                'month' => [
                    'deliveries' => $monthPerformance->sum('deliveries_completed'),
                    'failed' => $monthPerformance->sum('deliveries_failed'),
                    'average_delivery_time' => $this->calculateAverageMetric($monthPerformance, 'avg_delivery_time_minutes'),
                    'rating' => $this->calculateAverageMetric($monthPerformance, 'rating'),
                    'guest_rating' => $this->calculateAverageMetric($monthPerformance, 'guest_rating_avg'),
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
    public function getRecentAssignments($waiterId, $limit = 10): array
    {
        try {
            if (!$waiterId) {
                \Log::warning('getRecentAssignments called with empty waiterId');
                return [];
            }

            \Log::info('🔵 [SERVICE] getRecentAssignments querying:', [
                'waiter_id' => $waiterId,
                'limit' => $limit,
            ]);

            $deliveryTasks = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->select([
                    'id', 'order_id', 'room_id', 'floor_id', 'waiter_id', 'status', 'assignment_type',
                    'assigned_at', 'accepted_at', 'picked_up_at', 'on_delivery_at', 'delivered_at', 
                    'cancelled_at', 'remarks'
                ])
                ->with([
                    'order:id,order_number,guest_id,room_id,status',
                    'order.guest:id,first_name,last_name',
                    'floor:id,floor_number,name',
                    'order.room:id,room_number'
                ])
                ->orderBy('assigned_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn ($delivery) => [
                    'id' => $delivery->id,
                    'order_id' => $delivery->order_id,
                    'room_id' => $delivery->room_id,
                    'room_number' => $delivery->order?->room?->room_number ?? $delivery->room_id,
                    'floor_id' => $delivery->floor_id,
                    'floor_number' => $delivery->floor?->floor_number,
                    'guest_name' => ($delivery->order?->guest ? $delivery->order->guest->first_name . ' ' . $delivery->order->guest->last_name : 'N/A'),
                    'order_number' => $delivery->order?->order_number,
                    'status' => $delivery->status,
                    'order_status' => $delivery->status,  // Added: frontend expects this field name
                    'assignment_type' => $delivery->assignment_type ?? 'manual',
                    'assigned_at' => $delivery->assigned_at?->format('Y-m-d H:i:s'),
                    'accepted_at' => $delivery->accepted_at?->format('Y-m-d H:i:s'),
                    'picked_up_at' => $delivery->picked_up_at?->format('Y-m-d H:i:s'),
                    'on_delivery_at' => $delivery->on_delivery_at?->format('Y-m-d H:i:s'),
                    'delivered_at' => $delivery->delivered_at?->format('Y-m-d H:i:s'),
                    'delivery_time_minutes' => $delivery->getDeliveryDurationMinutes(),
                    'is_late' => $delivery->isLate(),
                    'remarks' => $delivery->remarks,
                ])
                ->toArray();

            \Log::info('✅ [SERVICE] getRecentAssignments result:', [
                'waiter_id' => $waiterId,
                'count' => count($deliveryTasks),
                'data' => $deliveryTasks,
            ]);

            return $deliveryTasks;
        } catch (\Throwable $e) {
            \Log::error('❌ Recent assignments error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
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

        // Add failed stage if failed
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

    
    public function getPendingCount($waiterId): int
    {
        try {
            return \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->where('status', 'assigned')
                ->count();
        } catch (\Throwable $e) {
            \Log::error('Pending count error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return 0;
        }
    }
    public function getActiveCount($waiterId): int
    {
        try {
            return \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
                ->count();
        } catch (\Throwable $e) {
            \Log::error('Active count error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return 0;
        }
    }
    public function getAllKitchenReadyOrders(): array
    {
        try {
            return \App\Models\Order::where('status', 'ready')
                ->with([
                    'guest:id,first_name,last_name',
                    'room:id,room_number',
                    'orderItems:id,order_id,menu_item_id,quantity,notes',
                    'orderItems.menuItem:id,name',
                    'chef:id,first_name,last_name'
                ])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'room_number' => $order->room?->room_number,
                    'guest_name' => ($order->guest ? $order->guest->first_name . ' ' . $order->guest->last_name : 'N/A'),
                    'items' => $order->orderItems?->count() ?? 0,
                    'special_requests' => $order->special_requests ?? 'None',
                    'priority' => $order->priority ?? 'normal',
                    'prepared_by' => ($order->chef ? $order->chef->first_name . ' ' . $order->chef->last_name : 'N/A'),
                    'ready_at' => $order->updated_at?->format('Y-m-d H:i:s'),
                    'wait_time_minutes' => $order->updated_at?->diffInMinutes(now()) ?? 0,
                    'total' => $order->total,
                    'items_detail' => $order->orderItems?->map(fn ($item) => [
                        'name' => $item->menuItem?->name ?? 'Unknown Item',
                        'quantity' => $item->quantity,
                        'notes' => $item->notes ?? 'None',
                    ])->toArray() ?? [],
                ])
                ->toArray();
        } catch (\Throwable $e) {
            \Log::error('All kitchen ready orders error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }
    public function getReadyForPickup($waiterId): array
    {
        try {
            \Log::info('🔵 [SERVICE] getReadyForPickup called', ['waiter_id' => $waiterId]);
            
            $results = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->whereIn('status', ['assigned', 'accepted'])
                ->with('order', 'order.guest', 'order.room', 'order.orderItems', 'order.orderItems.menuItem')
                ->orderBy('assigned_at', 'asc')
                ->get()
                ->filter(fn($assignment) => $assignment->order && $assignment->order->status === 'ready')
                ->map(function ($assignment) {
                    try {
                        return [
                            'id' => $assignment->id,
                            'order_id' => $assignment->order_id,
                            'order_number' => $assignment->order?->order_number,
                            'room_number' => $assignment->order?->room?->room_number,
                            'guest_name' => ($assignment->order?->guest ? $assignment->order->guest->first_name . ' ' . $assignment->order->guest->last_name : 'N/A'),
                            'items' => $assignment->order?->orderItems?->count() ?? 0,
                            'assigned_at' => $assignment->assigned_at?->format('Y-m-d H:i:s'),
                            'wait_time_minutes' => $assignment->assigned_at?->diffInMinutes(now()) ?? 0,
                            'order_status' => $assignment->order?->status,
                            'delivery_task_status' => $assignment->status,
                            'items_detail' => $assignment->order?->orderItems?->map(fn ($item) => [
                                'name' => $item->menuItem?->name ?? 'Unknown Item',
                                'quantity' => $item->quantity,
                                'notes' => $item->notes ?? 'None',
                            ])->toArray() ?? [],
                            'special_requests' => $assignment->order?->special_requests ?? 'None',
                        ];
                    } catch (\Throwable $e) {
                        \Log::error('Error mapping ready-for-pickup task', [
                            'task_id' => $assignment->id,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                })
                ->values()
                ->toArray();
            
            \Log::info('✅ [SERVICE] getReadyForPickup results', ['count' => count($results)]);
            
            return $results;
        } catch (\Throwable $e) {
            \Log::error('Ready for pickup error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
    public function getPendingPickupOrders($waiterId): array
    {
        try {
            \Log::info('🔵 [SERVICE] getPendingPickupOrders called', [
                'waiter_id' => $waiterId,
            ]);
            $assignments = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->where('status', 'assigned')
                ->with([
                    'order:id,order_number,room_id,guest_id,status,special_requests',
                    'order.guest:id,first_name,last_name',
                    'order.room:id,room_number',
                    'order.orderItems:id,order_id,menu_item_id,quantity,notes',
                    'order.orderItems.menuItem:id,name',
                    'assignedBy:id,first_name,last_name'
                ])
                ->whereHas('order', fn($q) => $q->whereIn('status', ['preparing', 'ready']))
                ->orderBy('assigned_at', 'asc')
                ->get();

            \Log::info('✅ [SERVICE] Found and filtered pending assignments', [
                'waiter_id' => $waiterId,
                'count' => $assignments->count(),
            ]);

            return $assignments->map(fn ($assignment) => [
                'id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'order_number' => $assignment->order?->order_number,
                'room_number' => $assignment->order?->room?->room_number,
                'guest_name' => ($assignment->order?->guest ? $assignment->order->guest->first_name . ' ' . $assignment->order->guest->last_name : 'N/A'),
                'items' => $assignment->order?->orderItems?->count() ?? 0,
                'priority' => $assignment->order?->priority ?? 'normal',
                'assigned_at' => $assignment->assigned_at?->format('Y-m-d H:i:s'),
                'order_status' => $assignment->order?->status,
                'is_ready' => $assignment->order?->status === 'ready',
                'assigned_by_name' => ($assignment->assignedBy ? $assignment->assignedBy->first_name . ' ' . $assignment->assignedBy->last_name : 'N/A'),
                'items_detail' => $assignment->order?->orderItems?->map(fn ($item) => [
                    'name' => $item->menuItem?->name ?? 'Unknown Item',
                    'quantity' => $item->quantity,
                    'notes' => $item->notes ?? 'None',
                ])->toArray() ?? [],
                'special_requests' => $assignment->order?->special_requests ?? 'None',
            ])->toArray();
        } catch (\Throwable $e) {
            \Log::error('❌ Pending pickup orders error', [
                'error' => $e->getMessage(),
                'waiter_id' => $waiterId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [];
        }
    }
    public function getOnDelivery($waiterId): array
    {
        try {
            \Log::info('🔵 [DASHBOARD] getOnDelivery called', ['waiter_id' => $waiterId]);
            
            // First, check if waiter_id is valid
            if (!$waiterId) {
                \Log::error('❌ [DASHBOARD] Invalid waiter_id passed to getOnDelivery', ['waiter_id' => $waiterId]);
                return [];
            }
            
            // Check if waiter exists
            $waiterExists = \App\Models\Waiter::find($waiterId);
            if (!$waiterExists) {
                \Log::error('❌ [DASHBOARD] Waiter not found in database', ['waiter_id' => $waiterId]);
                return [];
            }
            
            // Log all delivery tasks for this waiter
            $allWaiterTasks = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->select('id', 'order_id', 'status', 'created_at')
                ->get();
            
            \Log::debug('📊 [DASHBOARD] All tasks for waiter', [
                'waiter_id' => $waiterId,
                'total_tasks' => $allWaiterTasks->count(),
                'tasks_by_status' => $allWaiterTasks->groupBy('status')->map->count(),
            ]);
            
            // Get on_delivery tasks - use safer relationship loading
            $tasks = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->where('status', 'on_delivery')
                ->with('order', 'order.guest', 'order.room', 'assignedBy', 'floor')
                ->orderBy('picked_up_at', 'asc')
                ->get();
            
            \Log::info('✅ [DASHBOARD] Query executed, tasks found', [
                'waiter_id' => $waiterId,
                'count' => $tasks->count(),
                'task_ids' => $tasks->pluck('id')->toArray(),
            ]);
            
            $result = $tasks->map(function ($assignment) {
                try {
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
                } catch (\Throwable $e) {
                    \Log::error('Error mapping task', [
                        'task_id' => $assignment->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            })->toArray();
            
            \Log::info('✅ [DASHBOARD] Mapped results', [
                'count' => count($result),
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            \Log::error('❌ On delivery error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
    public function getCompletedDeliveries($waiterId, $limit = 10): array
    {
        try {
            $today = Carbon::today();
            $results = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->where('status', 'delivered')
                ->whereDate('delivered_at', $today)
                ->with('order', 'order.guest', 'order.room', 'assignedBy')
                ->orderBy('delivered_at', 'desc')
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
                            'delivered_at' => $assignment->delivered_at?->format('Y-m-d H:i:s'),
                            'delivery_time_minutes' => $assignment->getDeliveryDurationMinutes(),
                            'remarks' => $assignment->remarks ?? 'None',
                            'status' => $assignment->status,
                            'order_status' => $assignment->status,
                        ];
                    } catch (\Throwable $e) {
                        \Log::error('Error mapping completed delivery', [
                            'task_id' => $assignment->id,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                })
                ->toArray();
            
            \Log::info('✅ [SERVICE] getCompletedDeliveries results', ['count' => count($results)]);
            return $results;
        } catch (\Throwable $e) {
            \Log::error('Completed deliveries error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
    public function getFailedDeliveries($waiterId, $limit = 10): array
    {
        try {
            $today = Carbon::today();
            $results = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->where('status', 'cancelled')
                ->whereDate('cancelled_at', $today)
                ->with('order', 'order.guest', 'order.room', 'assignedBy')
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
            
            \Log::info('✅ [SERVICE] getFailedDeliveries results', ['count' => count($results)]);
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

            $dailyData = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i);
                $dayName = $date->format('l');

                $performance = WaiterPerformance::where('waiter_id', $waiterId)
                    ->where('metric_date', $date)
                    ->first();

                $dailyData[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $dayName,
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
            \Log::info('🔵 [SERVICE] getQuickStats called', [
                'waiter_id' => $waiterId,
            ]);
            
            $today = Carbon::today();
            
            // OPTIMIZATION: Use raw database aggregation on new delivery_tasks table
            $stats = \App\Models\DeliveryTask::where('waiter_id', $waiterId)
                ->whereDate('assigned_at', $today)
                ->selectRaw('
                    SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status IN ("accepted", "picked_up", "on_delivery") THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as failed
                ')
                ->first();

            $result = [
                'pending' => (int)($stats->pending ?? 0),
                'active' => (int)($stats->active ?? 0),
                'completed' => (int)($stats->completed ?? 0),
                'failed' => (int)($stats->failed ?? 0),
            ];

            \Log::info(' [SERVICE] Quick stats calculated', [
                'waiter_id' => $waiterId,
                'stats' => $result,
            ]);

            return $result;
        } catch (\Throwable $e) {
            \Log::error('❌ Quick stats error', [
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
