<?php

namespace App\Services\Manager;

use App\Models\CheckIn;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Models\Waiter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function getDashboardStats(): array
    {
        return [
            'occupancy' => $this->getOccupancyStats(),
            'reception' => $this->getReceptionStats(),
            'revenue' => $this->getRevenueStats(),
            'orders' => $this->getOrderStats(),
            'kitchen' => $this->getKitchenStats(),
            'waiters' => $this->getWaiterStats(),
            'housekeeping' => $this->getHousekeepingStats(),
            'laundry' => $this->getLaundryStats(),
            'complaints' => $this->getComplaintStats(),
            'staff' => $this->getStaffStats(),
        ];
    }
    public function getOccupancyStats(): array
    {
        try {
            $today = Carbon::today();
            
            $totalRooms = Room::count();
            $checkedInRooms = CheckIn::whereDate('checked_in_at', $today)
                ->whereNull('checked_out_at')
                ->distinct('room_id')
                ->count('room_id');
            
            $occupancyRate = $totalRooms > 0 
                ? round(($checkedInRooms / $totalRooms) * 100, 2)
                : 0;

            return [
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $checkedInRooms,
                'available_rooms' => $totalRooms - $checkedInRooms,
                'occupancy_rate' => $occupancyRate,
                'checked_in_guests' => CheckIn::whereDate('checked_in_at', $today)
                    ->whereNull('checked_out_at')
                    ->count(),
                'checked_out_guests' => CheckIn::whereDate('checked_out_at', $today)
                    ->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Occupancy stats error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [
                'total_rooms' => 0,
                'occupied_rooms' => 0,
                'available_rooms' => 0,
                'occupancy_rate' => 0,
                'checked_in_guests' => 0,
                'checked_out_guests' => 0,
            ];
        }
    }
    public function getReceptionStats(): array
    {
        try {
            $today = Carbon::today();
            
            // Total reservations (all statuses)
            $totalReservations = Reservation::count();
            
            // Reservations for today
            $todayReservations = Reservation::whereDate('check_in_date', $today)->count();
            
            // Today's check-ins
            $todayCheckIns = CheckIn::whereDate('checked_in_at', $today)
                ->whereNull('checked_out_at')
                ->count();
            
            // Today's check-outs
            $todayCheckOuts = CheckIn::whereDate('checked_out_at', $today)->count();
            
            // Room availability
            $totalRooms = Room::count();
            $occupiedRooms = CheckIn::whereDate('checked_in_at', $today)
                ->whereNull('checked_out_at')
                ->distinct('room_id')
                ->count('room_id');
            
            $availableRooms = $totalRooms - $occupiedRooms;

            return [
                'total_reservations' => $totalReservations,
                'today_reservations' => $todayReservations,
                'today_check_ins' => $todayCheckIns,
                'today_check_outs' => $todayCheckOuts,
                'available_rooms' => $availableRooms,
                'occupied_rooms' => $occupiedRooms,
                'total_rooms' => $totalRooms,
            ];
        } catch (\Throwable $e) {
            Log::error('Reception stats error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            
            // Return safe defaults on error
            return [
                'total_reservations' => 0,
                'today_reservations' => 0,
                'today_check_ins' => 0,
                'today_check_outs' => 0,
                'available_rooms' => 0,
                'occupied_rooms' => 0,
                'total_rooms' => 0,
            ];
        }
    }
    public function getRevenueStats(): array
    {
        try {
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();
            $startOfWeek = Carbon::now()->startOfWeek();

            $dailyRevenue = Order::whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('total');

            $weeklyRevenue = Order::whereBetween('created_at', [$startOfWeek, now()])
                ->where('status', 'completed')
                ->sum('total');

            $monthlyRevenue = Order::whereBetween('created_at', [$startOfMonth, now()])
                ->where('status', 'completed')
                ->sum('total');

            $pendingPayments = Order::whereDate('created_at', $today)
                ->where('payment_type', 'pending')
                ->sum('total');

            return [
                'daily_revenue' => round($dailyRevenue, 2),
                'weekly_revenue' => round($weeklyRevenue, 2),
                'monthly_revenue' => round($monthlyRevenue, 2),
                'pending_payments' => round($pendingPayments, 2),
            ];
        } catch (\Throwable $e) {
            Log::error('Revenue stats error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return [
                'daily_revenue' => 0,
                'weekly_revenue' => 0,
                'monthly_revenue' => 0,
                'pending_payments' => 0,
            ];
        }
    }
    public function getOrderStats(): array
    {
        try {
            $today = Carbon::today();

            return [
                'total_orders' => Order::whereDate('created_at', $today)->count(),
                'completed_orders' => Order::whereDate('created_at', $today)
                    ->where('status', 'completed')
                    ->count(),
                'pending_orders' => Order::whereDate('created_at', $today)
                    ->where('status', 'pending')
                    ->count(),
                'cancelled_orders' => Order::whereDate('created_at', $today)
                    ->where('status', 'cancelled')
                    ->count(),
                'delivery_in_progress' => Order::whereDate('created_at', $today)
                    ->where('status', 'delivery')
                    ->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Order stats error: ' . $e->getMessage());
            return [
                'total_orders' => 0,
                'completed_orders' => 0,
                'pending_orders' => 0,
                'cancelled_orders' => 0,
                'delivery_in_progress' => 0,
            ];
        }
    }
    public function getKitchenStats(): array
    {
        try {
            $today = Carbon::today();

            return [
                'total_orders' => Order::whereDate('created_at', $today)
                    ->whereIn('status', ['preparing', 'ready', 'completed'])
                    ->count(),
                'ready_orders' => Order::whereDate('created_at', $today)
                    ->where('status', 'ready')
                    ->count(),
                'preparing_orders' => Order::whereDate('created_at', $today)
                    ->where('status', 'preparing')
                    ->count(),
                'delayed_orders' => Order::whereDate('created_at', $today)
                    ->where('status', 'preparing')
                    ->where('created_at', '<', now()->subMinutes(30))
                    ->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Kitchen stats error: ' . $e->getMessage());
            return [
                'total_orders' => 0,
                'ready_orders' => 0,
                'preparing_orders' => 0,
                'delayed_orders' => 0,
            ];
        }
    }
    public function getWaiterStats(): array
    {
        try {
            $today = Carbon::today();

            $waiters = Waiter::all();
            $activeWaiters = Waiter::where('status', 'active')->count();
            $onBreakWaiters = Waiter::where('status', 'on_break')->count();
            $inactiveWaiters = Waiter::where('status', 'inactive')->count();

            return [
                'total_waiters' => $waiters->count(),
                'active_waiters' => $activeWaiters,
                'on_break_waiters' => $onBreakWaiters,
                'inactive_waiters' => $inactiveWaiters,
                'total_deliveries_today' => Order::whereDate('created_at', $today)
                    ->where('status', 'completed')
                    ->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Waiter stats error: ' . $e->getMessage());
            return [
                'total_waiters' => 0,
                'active_waiters' => 0,
                'on_break_waiters' => 0,
                'inactive_waiters' => 0,
                'total_deliveries_today' => 0,
            ];
        }
    }
    public function getHousekeepingStats(): array
    {
        try {
            return [
                'dirty_rooms' => Room::where('status', 'dirty')->count(),
                'cleaning_in_progress' => Room::where('status', 'cleaning')->count(),
                'clean_rooms' => Room::where('status', 'available')->count(),
                'maintenance_required' => Room::where('status', 'maintenance')->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Housekeeping stats error: ' . $e->getMessage());
            return [
                'dirty_rooms' => 0,
                'cleaning_in_progress' => 0,
                'clean_rooms' => 0,
                'maintenance_required' => 0,
            ];
        }
    }
    public function getLaundryStats(): array
    {
        try {
            // This would integrate with laundry module when available
            return [
                'pending_requests' => 0,
                'processing' => 0,
                'completed_today' => 0,
            ];
        } catch (\Throwable $e) {
            Log::error('Laundry stats error: ' . $e->getMessage());
            return [
                'pending_requests' => 0,
                'processing' => 0,
                'completed_today' => 0,
            ];
        }
    }
    public function getComplaintStats(): array
    {
        try {
            return [
                'total_complaints' => \App\Models\ComplaintTicket::where('created_at', '>=', Carbon::today())->count(),
                'open_complaints' => \App\Models\ComplaintTicket::open()->where('created_at', '>=', Carbon::today())->count(),
                'urgent_complaints' => \App\Models\ComplaintTicket::urgent()->where('created_at', '>=', Carbon::today())->count(),
                'resolved_complaints' => \App\Models\ComplaintTicket::where('status', 'resolved')
                    ->where('created_at', '>=', Carbon::today())
                    ->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Complaint stats error: ' . $e->getMessage());
            return [
                'total_complaints' => 0,
                'open_complaints' => 0,
                'urgent_complaints' => 0,
                'resolved_complaints' => 0,
            ];
        }
    }
    public function getStaffStats(): array
    {
        try {
            return [
                'total_staff' => User::where('role', '!=', 'guest')->count(),
                'on_duty' => User::where('role', '!=', 'guest')->count(), // This would need a shift table
                'on_break' => 0,
                'off_duty' => 0,
            ];
        } catch (\Throwable $e) {
            Log::error('Staff stats error: ' . $e->getMessage());
            return [
                'total_staff' => 0,
                'on_duty' => 0,
                'on_break' => 0,
                'off_duty' => 0,
            ];
        }
    }
    public function getDailyStats($days = 7): array
    {
        try {
            $stats = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->toDateString();
                
                $stats[] = [
                    'date' => $date,
                    'revenue' => Order::whereDate('created_at', $date)
                        ->where('status', 'completed')
                        ->sum('total'),
                    'orders' => Order::whereDate('created_at', $date)->count(),
                    'occupancy_rate' => $this->getOccupancyRateForDate($date),
                ];
            }

            return $stats;
        } catch (\Throwable $e) {
            Log::error('Daily stats error: ' . $e->getMessage());
            return [];
        }
    }
    private function getOccupancyRateForDate($date): float
    {
        try {
            $totalRooms = Room::count();
            if ($totalRooms === 0) {
                return 0;
            }

            $occupiedRooms = CheckIn::whereDate('checked_in_at', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('checked_out_at')
                          ->orWhereDate('checked_out_at', '>=', $date);
                })
                ->distinct('room_id')
                ->count('room_id');

            return round(($occupiedRooms / $totalRooms) * 100, 2);
        } catch (\Throwable $e) {
            Log::error('Occupancy rate calculation error: ' . $e->getMessage());
            return 0;
        }
    }
    public function getTopSellingItems($limit = 5): array
    {
        try {
            return DB::table('order_items')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->select('menu_items.name', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->groupBy('menu_items.id', 'menu_items.name')
                ->orderByDesc('total_sold')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            Log::error('Top selling items error: ' . $e->getMessage());
            return [];
        }
    }
    public function getPerformanceSummary(): array
    {
        try {
            return [
                'order_completion_rate' => $this->getOrderCompletionRate(),
                'avg_delivery_time' => $this->getAverageDeliveryTime(),
                'customer_satisfaction' => $this->getCustomerSatisfaction(),
                'staff_efficiency' => $this->getStaffEfficiency(),
            ];
        } catch (\Throwable $e) {
            Log::error('Performance summary error: ' . $e->getMessage());
            return [
                'order_completion_rate' => 0,
                'avg_delivery_time' => null,
                'customer_satisfaction' => 0,
                'staff_efficiency' => 0,
            ];
        }
    }
    private function getOrderCompletionRate(): float
    {
        try {
            $today = Carbon::today();
            $totalOrders = Order::whereDate('created_at', $today)->count();
            
            if ($totalOrders === 0) {
                return 0;
            }

            $completedOrders = Order::whereDate('created_at', $today)
                ->where('status', 'completed')
                ->count();

            return round(($completedOrders / $totalOrders) * 100, 2);
        } catch (\Throwable $e) {
            Log::error('Order completion rate error: ' . $e->getMessage());
            return 0;
        }
    }
    private function getAverageDeliveryTime(): ?float
    {
        try {
            $avgTime = Order::where('status', 'completed')
                ->where('completed_at', '>=', Carbon::today())
                ->average(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, completed_at)'));

            return $avgTime ? round($avgTime, 2) : null;
        } catch (\Throwable $e) {
            Log::error('Average delivery time error: ' . $e->getMessage());
            return null;
        }
    }
    private function getCustomerSatisfaction(): float
    {
        try {
            $avgRating = \App\Models\ComplaintTicket::whereNotNull('satisfaction_rating')
                ->average('satisfaction_rating');

            return $avgRating ? round($avgRating, 2) : 0;
        } catch (\Throwable $e) {
            Log::error('Customer satisfaction error: ' . $e->getMessage());
            return 0;
        }
    }
    private function getStaffEfficiency(): float
    {
        try {
            $metrics = \App\Models\PerformanceMetric::where('metric_date', Carbon::today())
                ->average('completion_rate');

            return $metrics ? round($metrics, 2) : 0;
        } catch (\Throwable $e) {
            Log::error('Staff efficiency error: ' . $e->getMessage());
            return 0;
        }
    }
}
