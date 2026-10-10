<?php

namespace App\Services\Manager;

use App\Models\Room;
use App\Models\CheckIn;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Order;
use App\Models\Guest;
use App\Models\HotelUser;
use App\Models\ManagerActivityLog;
use App\Models\HousekeepingTask;
use App\Models\RoomServiceDelivery;
use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Models\LaundryRequest;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ManagerDashboardService
{
    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: request()->query('hotel_id')
            ?: request()->input('hotel_id')
            ?: app(TenantContext::class)->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function statistics(): array
    {
        $hotelId = $this->getHotelId();
        $today = Carbon::today();

        $orders = Order::withoutGlobalScopes();
        if ($hotelId && Schema::hasColumn('orders', 'hotel_id')) {
            $orders->where('hotel_id', $hotelId);
        } elseif ($hotelId) {
            $orders->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        } else {
            $orders->whereRaw('1 = 0');
        }

        $totalOrders = (clone $orders)->count();
        $pendingOrders = (clone $orders)->where('status', Order::STATUS_PENDING)->count();
        $preparingOrders = (clone $orders)->where('status', Order::STATUS_PREPARING)->count();
        $readyOrders = (clone $orders)->where('status', Order::STATUS_READY)->count();
        $servedOrders = (clone $orders)->where('status', Order::STATUS_SERVED)->count();

        $deliveries = DeliveryTask::withoutGlobalScopes();
        if ($hotelId && Schema::hasColumn('delivery_tasks', 'hotel_id')) {
            $deliveries->where('hotel_id', $hotelId);
        } elseif ($hotelId) {
            $deliveries->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        } else {
            $deliveries->whereRaw('1 = 0');
        }

        $totalDeliveries = (clone $deliveries)->count();
        $activeDeliveries = (clone $deliveries)->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])->count();
        $completedDeliveries = (clone $deliveries)->where('status', 'delivered')->count();

        $roomQuery = Room::withoutGlobalScopes();
        $checkInQuery = CheckIn::withoutGlobalScopes();
        $resQuery = Reservation::withoutGlobalScopes();
        $guestQuery = Guest::withoutGlobalScopes();

        if ($hotelId) {
            $roomQuery->where('hotel_id', $hotelId);
            $checkInQuery->where('hotel_id', $hotelId);
            $resQuery->where('hotel_id', $hotelId);
            $guestQuery->where('hotel_id', $hotelId);
        } else {
            $roomQuery->whereRaw('1 = 0');
            $checkInQuery->whereRaw('1 = 0');
            $resQuery->whereRaw('1 = 0');
            $guestQuery->whereRaw('1 = 0');
        }

        $totalRooms = (clone $roomQuery)->count();
        $occupiedRooms = (clone $checkInQuery)->whereNull('checked_out_at')->count();
        $availableRooms = (clone $roomQuery)->where('status', 'available')->count();
        $reservedRooms = (clone $resQuery)->where('status', 'confirmed')->count();
        $maintenanceRooms = (clone $roomQuery)->where('status', 'maintenance')->count();

        $totalGuests = (clone $guestQuery)->count();
        $checkedInGuests = (clone $checkInQuery)->whereNull('checked_out_at')->count();
        $guestCheckouts = (clone $checkInQuery)->whereDate('expected_check_out_at', $today)->count();
        $todayReservations = (clone $resQuery)->whereDate('created_at', $today)->count();

        $laundryQuery = LaundryRequest::withoutGlobalScopes();
        $housekeepingQuery = HousekeepingTask::withoutGlobalScopes();
        if ($hotelId && Schema::hasColumn('laundry_requests', 'hotel_id')) {
            $laundryQuery->where('hotel_id', $hotelId);
        } elseif ($hotelId) {
            $laundryQuery->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        } else {
            $laundryQuery->whereRaw('1 = 0');
        }

        if ($hotelId && Schema::hasColumn('housekeeping_tasks', 'hotel_id')) {
            $housekeepingQuery->where('hotel_id', $hotelId);
        } elseif ($hotelId) {
            $housekeepingQuery->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        } else {
            $housekeepingQuery->whereRaw('1 = 0');
        }

        $pendingLaundry = (clone $laundryQuery)->where('status', 'pending')->count();
        $pendingHousekeeping = (clone $housekeepingQuery)->where('status', 'pending')->count();

        $activeStaff = 0;
        if ($hotelId) {
            $activeStaff = HotelUser::where('hotel_id', $hotelId)->where('is_active', true)->count();
        }

        $todayRevenue = (float) ((clone $orders)->whereDate('served_at', $today)->where('status', Order::STATUS_SERVED)->sum('total') ?: 0);
        $monthlyRevenue = (float) ((clone $orders)->whereMonth('served_at', $today->month)->where('status', Order::STATUS_SERVED)->sum('total') ?: 0);

        if ($todayRevenue == 0 && $hotelId) {
            try {
                $todayRevenue = (float) DB::table('payments')
                    ->where('status', 'completed')
                    ->when(Schema::hasColumn('payments', 'hotel_id'), fn($q) => $q->where('hotel_id', $hotelId))
                    ->whereDate('created_at', $today)
                    ->sum('amount');
                $monthlyRevenue = (float) DB::table('payments')
                    ->where('status', 'completed')
                    ->when(Schema::hasColumn('payments', 'hotel_id'), fn($q) => $q->where('hotel_id', $hotelId))
                    ->whereMonth('created_at', $today->month)
                    ->sum('amount');
            } catch (\Throwable $e) {}
        }

        return [
            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'availableRooms' => $availableRooms,
            'reservedRooms' => $reservedRooms,
            'maintenanceRooms' => $maintenanceRooms,

            'totalGuests' => $totalGuests,
            'checkedInGuests' => $checkedInGuests,
            'guestCheckouts' => $guestCheckouts,
            'todayReservations' => $todayReservations,

            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'preparingOrders' => $preparingOrders,
            'readyOrders' => $readyOrders,
            'servedOrders' => $servedOrders,

            'totalDeliveries' => $totalDeliveries,
            'activeDeliveries' => $activeDeliveries,
            'completedDeliveries' => $completedDeliveries,

            'pendingLaundry' => $pendingLaundry,
            'pendingHousekeeping' => $pendingHousekeeping,

            'activeStaff' => $activeStaff,

            'todayRevenue' => round($todayRevenue, 2),
            'monthlyRevenue' => round($monthlyRevenue, 2),
        ];
    }

    public function revenueSummary(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeekStart = Carbon::now()->startOfWeek();
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisYearStart = Carbon::now()->startOfYear();

        return [
            'today' => $this->calculateRevenue($today, $today),
            'yesterday' => $this->calculateRevenue($yesterday, $yesterday),
            'thisWeek' => $this->calculateRevenue($thisWeekStart, Carbon::now()),
            'thisMonth' => $this->calculateRevenue($thisMonthStart, Carbon::now()),
            'thisYear' => $this->calculateRevenue($thisYearStart, Carbon::now()),
        ];
    }

    private function calculateRevenue(Carbon $start, Carbon $end): float
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return 0;

        try {
            if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'hotel_id')) {
                return (float) DB::table('payments')
                    ->where('hotel_id', $hotelId)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->sum('amount');
            }
        } catch (\Throwable $e) {}

        $orderQuery = Order::withoutGlobalScopes();
        if (Schema::hasColumn('orders', 'hotel_id')) {
            $orderQuery->where('hotel_id', $hotelId);
        } else {
            $orderQuery->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        }

        return (float) ($orderQuery->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])->sum('total') ?: 0);
    }

    public function occupancySummary(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) {
            return [
                'totalRooms' => 0,
                'occupiedRooms' => 0,
                'availableRooms' => 0,
                'reservedRooms' => 0,
                'maintenanceRooms' => 0,
                'occupancyRate' => 0,
            ];
        }

        $totalRooms = Room::withoutGlobalScopes()->where('hotel_id', $hotelId)->count();
        $occupiedRooms = CheckIn::withoutGlobalScopes()->where('hotel_id', $hotelId)->whereNull('checked_out_at')->count();
        $availableRooms = Room::withoutGlobalScopes()->where('hotel_id', $hotelId)->where('status', 'available')->count();
        $reservedRooms = Reservation::withoutGlobalScopes()->where('hotel_id', $hotelId)->where('status', 'confirmed')->count();
        $maintenanceRooms = Room::withoutGlobalScopes()->where('hotel_id', $hotelId)->where('status', 'maintenance')->count();

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        return [
            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'availableRooms' => $availableRooms,
            'reservedRooms' => $reservedRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupancyRate' => $occupancyRate,
        ];
    }

    public function reservationSummary(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) {
            return [
                'pending' => 0,
                'confirmed' => 0,
                'checkedIn' => 0,
                'checkedOut' => 0,
                'cancelled' => 0,
            ];
        }

        return [
            'pending' => Reservation::withoutGlobalScopes()->where('hotel_id', $hotelId)->where('status', 'pending')->count(),
            'confirmed' => Reservation::withoutGlobalScopes()->where('hotel_id', $hotelId)->where('status', 'confirmed')->count(),
            'checkedIn' => CheckIn::withoutGlobalScopes()->where('hotel_id', $hotelId)->whereNull('checked_out_at')->count(),
            'checkedOut' => CheckIn::withoutGlobalScopes()->where('hotel_id', $hotelId)->whereNotNull('checked_out_at')->count(),
            'cancelled' => Reservation::withoutGlobalScopes()->where('hotel_id', $hotelId)->where('status', 'cancelled')->count(),
        ];
    }

    public function revenueChart(string $period = 'monthly'): array
    {
        $data = [];

        if ($period === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $revenue = $this->calculateRevenue($date, $date);
                $data[] = [
                    'label' => $date->format('D'),
                    'revenue' => $revenue,
                ];
            }
        } elseif ($period === 'monthly') {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $revenue = $this->calculateRevenue($date, $date);
                $data[] = [
                    'label' => $date->format('M d'),
                    'revenue' => $revenue,
                ];
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $revenue = $this->calculateRevenue($date->startOfMonth(), $date->endOfMonth());
                $data[] = [
                    'label' => $date->format('M Y'),
                    'revenue' => $revenue,
                ];
            }
        }

        return $data;
    }

    public function occupancyChart(): array
    {
        $hotelId = $this->getHotelId();
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $total = $hotelId ? Room::withoutGlobalScopes()->where('hotel_id', $hotelId)->count() : 0;
            $occupied = 0;

            if ($hotelId) {
                $occupied = CheckIn::withoutGlobalScopes()
                    ->where('hotel_id', $hotelId)
                    ->where(function ($query) use ($date) {
                        $query->whereDate('checked_in_at', '<=', $date)
                            ->where(function ($q) use ($date) {
                                $q->whereDate('expected_check_out_at', '>=', $date)
                                    ->orWhereNull('checked_out_at');
                            });
                    })->count();
            }

            $data[] = [
                'label' => $date->format('D'),
                'occupied' => $occupied,
                'available' => max(0, $total - $occupied),
            ];
        }

        return $data;
    }

    public function completeDashboard(): array
    {
        return [
            'statistics' => $this->statistics(),
            'revenue' => $this->revenueSummary(),
            'occupancy' => $this->occupancySummary(),
            'reservations' => $this->reservationSummary(),
            'staff' => $this->getStaff(),
            'recentOrders' => $this->getRecentOrders(),
            'deliveries' => $this->getDeliveries(),
            'housekeeping' => $this->getHousekeeping(),
            'laundry' => $this->getLaundry(),
            'recentActivities' => $this->getActivities(),
            'notifications' => [],
            'revenueChart' => $this->revenueChart(),
            'occupancyChart' => $this->occupancyChart(),
            'waiters' => $this->getWaiters(),
        ];
    }

    public function getStaff(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return [];

        return HotelUser::where('hotel_id', $hotelId)
            ->with(['user', 'roleModel'])
            ->whereHas('user', fn($q) => $q->where('role', '!=', 'guest'))
            ->limit(10)
            ->get()
            ->map(function ($membership) {
                $user = $membership->user;
                $roleName = $membership->roleModel?->name ?? ucfirst($membership->role ?: 'staff');
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,
                    'role' => $roleName,
                    'status' => ($user->is_active && $membership->is_active) ? 'active' : 'inactive',
                ];
            })
            ->toArray();
    }

    public function getRecentOrders(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return [];

        $orderQuery = Order::withoutGlobalScopes();
        if (Schema::hasColumn('orders', 'hotel_id')) {
            $orderQuery->where('hotel_id', $hotelId);
        } else {
            $orderQuery->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        }

        return $orderQuery->with('guest', 'room', 'orderItems')
            ->select('id', 'order_number', 'guest_id', 'room_id', 'status', 'total', 'created_at')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'guestName' => $order->guest ? ($order->guest->first_name . ' ' . $order->guest->last_name) : 'Unknown',
                    'roomNumber' => $order->room?->room_number,
                    'itemCount' => $order->orderItems->count(),
                    'status' => $order->status,
                    'total' => $order->total,
                    'createdAt' => $order->created_at,
                ];
            })
            ->toArray();
    }

    public function getDeliveries(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return [];

        $query = DeliveryTask::withoutGlobalScopes();
        if (Schema::hasColumn('delivery_tasks', 'hotel_id')) {
            $query->where('hotel_id', $hotelId);
        } else {
            $query->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        }

        return $query->with('room', 'order', 'waiter')
            ->select('id', 'room_id', 'order_id', 'status', 'waiter_id', 'assigned_at', 'delivered_at')
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery', 'delivered'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'roomNumber' => $delivery->room?->room_number,
                    'orderNumber' => $delivery->order?->order_number,
                    'waiterName' => $delivery->waiter?->name ?? 'Unassigned',
                    'status' => $delivery->status,
                    'time' => $delivery->delivered_at ?: $delivery->assigned_at,
                ];
            })
            ->toArray();
    }

    public function getHousekeeping(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return [];

        $query = HousekeepingTask::withoutGlobalScopes();
        if (Schema::hasColumn('housekeeping_tasks', 'hotel_id')) {
            $query->where('hotel_id', $hotelId);
        } else {
            $query->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        }

        return $query->with('room', 'assignedTo')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'roomId' => $task->room_id,
                    'roomNumber' => $task->room?->room_number,
                    'assignedTo' => $task->assignedTo ? ($task->assignedTo->first_name . ' ' . $task->assignedTo->last_name) : 'Unassigned',
                    'status' => $task->status,
                    'taskType' => $task->task_type,
                    'priority' => $task->priority,
                    'scheduledTime' => $task->scheduled_time,
                ];
            })
            ->toArray();
    }

    public function getLaundry(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return [];

        $query = LaundryRequest::withoutGlobalScopes();
        if (Schema::hasColumn('laundry_requests', 'hotel_id')) {
            $query->where('hotel_id', $hotelId);
        } else {
            $query->whereHas('room', fn($q) => $q->where('hotel_id', $hotelId));
        }

        return $query->with('room', 'guest')
            ->select('id', 'room_id', 'guest_id', 'status', 'requested_time', 'pickup_time', 'delivery_time', 'cost')
            ->whereIn('status', ['pending', 'processing', 'ready'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'roomId' => $request->room_id,
                    'roomNumber' => $request->room?->room_number,
                    'guestName' => $request->guest ? ($request->guest->first_name . ' ' . $request->guest->last_name) : 'N/A',
                    'status' => $request->status,
                    'requestedTime' => $request->requested_time,
                    'pickupTime' => $request->pickup_time,
                    'deliveryTime' => $request->delivery_time,
                    'cost' => $request->cost,
                ];
            })
            ->toArray();
    }

    public function getActivities(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return [];

        $query = ManagerActivityLog::withoutGlobalScopes();
        if (Schema::hasColumn('manager_activity_logs', 'hotel_id')) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->select('id', 'manager_id', 'action', 'description', 'created_at')
            ->with('manager')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'manager' => $activity->manager ? ($activity->manager->first_name . ' ' . $activity->manager->last_name) : 'System',
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'timestamp' => $activity->created_at,
                ];
            })
            ->toArray();
    }

    public function getWaiters(): array
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return [];

        $query = Waiter::withoutGlobalScopes();
        if (Schema::hasColumn('waiters', 'hotel_id')) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->with('user')
            ->select('id', 'user_id', 'section', 'status', 'shift', 'experience_level')
            ->where('status', 'active')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($waiter) {
                return [
                    'id' => $waiter->id,
                    'userId' => $waiter->user_id,
                    'name' => $waiter->user ? ($waiter->user->first_name . ' ' . $waiter->user->last_name) : 'N/A',
                    'section' => $waiter->section,
                    'status' => $waiter->status,
                    'shift' => $waiter->shift,
                    'experienceLevel' => $waiter->experience_level,
                ];
            })
            ->toArray();
    }
}

