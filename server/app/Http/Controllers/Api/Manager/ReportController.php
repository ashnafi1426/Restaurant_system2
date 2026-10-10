<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Waiter;
use App\Models\DeliveryTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\TenantContext;
class ReportController extends Controller
{
    private function resolveTenant(Request $request): ?string
    {
        $hotelId = $request->input('hotel_id')
            ?: $request->header('X-Hotel-ID')
            ?: app(TenantContext::class)->getHotelId();

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function revenue(Request $request): JsonResponse
    {
        try {
            $this->resolveTenant($request);
            $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());
            $groupBy = $request->input('group_by', 'day');

            $revenueBySource = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->select('payment_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_type')
                ->get();

            $revenueOverTime = $this->getRevenueOverTime($startDate, $endDate, $groupBy);

            $paymentMethods = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get();

            $totalRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('amount');

            $totalTransactions = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count();

            $averageTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_revenue' => $totalRevenue,
                        'total_transactions' => $totalTransactions,
                        'average_transaction' => round($averageTransaction, 2),
                        'period' => ['start' => $startDate, 'end' => $endDate],
                    ],
                    'revenue_by_source' => $revenueBySource,
                    'revenue_over_time' => $revenueOverTime,
                    'payment_methods' => $paymentMethods,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate revenue report: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function operations(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->resolveTenant($request);
            $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());

            $orderStats = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders'),
                    DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders'),
                    DB::raw('AVG(total_price) as average_order_value')
                )
                ->first();

            $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get();

            $peakHours = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as order_count'))
                ->groupBy('hour')
                ->orderBy('order_count', 'desc')
                ->limit(5)
                ->get();

            $deliveryStats = DeliveryTask::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('COUNT(*) as total_deliveries'),
                    DB::raw('AVG(TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at)) as avg_delivery_time'),
                    DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as successful_deliveries')
                )
                ->first();

            $popularItemsQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$startDate, $endDate]);

            if ($hotelId) {
                $popularItemsQuery->where('orders.hotel_id', $hotelId);
            }

            $popularItems = $popularItemsQuery
                ->select(
                    'order_items.item_name',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('COUNT(DISTINCT order_items.order_id) as order_count')
                )
                ->groupBy('order_items.item_name')
                ->orderBy('total_quantity', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'order_statistics' => $orderStats,
                    'orders_by_status' => $ordersByStatus,
                    'peak_hours' => $peakHours,
                    'delivery_performance' => $deliveryStats,
                    'popular_items' => $popularItems,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate operations report: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function staff(Request $request): JsonResponse
    {
        try {
            $this->resolveTenant($request);
            $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());

            $waiterPerformance = Waiter::with('user')
                ->select('waiters.*')
                ->withCount([
                    'deliveryTasks as total_deliveries' => function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate, $endDate]);
                    },
                    'deliveryTasks as completed_deliveries' => function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate, $endDate])
                          ->where('status', 'delivered');
                    }
                ])
                ->where('status', 'active')
                ->get()
                ->map(function ($waiter) {
                    return [
                        'id' => $waiter->id,
                        'name' => $waiter->user ? "{$waiter->user->first_name} {$waiter->user->last_name}" : 'Unknown',
                        'section' => $waiter->section,
                        'total_deliveries' => $waiter->total_deliveries ?? 0,
                        'completed_deliveries' => $waiter->completed_deliveries ?? 0,
                        'completion_rate' => $waiter->total_deliveries > 0
                            ? round(($waiter->completed_deliveries / $waiter->total_deliveries) * 100, 2)
                            : 0,
                    ];
                });

            $staffSummary = [
                'total_active_staff' => Waiter::where('status', 'active')->count(),
                'total_staff' => Waiter::count(),
                'average_deliveries_per_waiter' => round($waiterPerformance->avg('total_deliveries'), 2),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $staffSummary,
                    'waiter_performance' => $waiterPerformance,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate staff report: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function occupancy(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());

            $totalRooms = Room::where('is_active', true)->count();

            $occupiedRooms = Room::where('is_active', true)
                ->where('status', 'occupied')
                ->count();

            $currentOccupancyRate = $totalRooms > 0
                ? round(($occupiedRooms / $totalRooms) * 100, 2)
                : 0;

            $checkInStats = CheckIn::whereBetween('check_in_date', [$startDate, $endDate])
                ->select(
                    DB::raw('COUNT(*) as total_check_ins'),
                    DB::raw('SUM(CASE WHEN check_out_date IS NOT NULL THEN 1 ELSE 0 END) as total_check_outs'),
                    DB::raw('AVG(DATEDIFF(check_out_date, check_in_date)) as average_stay_duration')
                )
                ->first();

            $reservationStats = Reservation::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('COUNT(*) as total_reservations'),
                    DB::raw('SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed_reservations'),
                    DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_reservations')
                )
                ->first();

            $occupancyOverTime = $this->getOccupancyOverTime($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_occupancy' => [
                        'total_rooms' => $totalRooms,
                        'occupied_rooms' => $occupiedRooms,
                        'available_rooms' => $totalRooms - $occupiedRooms,
                        'occupancy_rate' => $currentOccupancyRate,
                    ],
                    'check_in_statistics' => $checkInStats,
                    'reservation_statistics' => $reservationStats,
                    'occupancy_over_time' => $occupancyOverTime,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate occupancy report: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function summary(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'today');

            $dates = $this->getPeriodDates($period);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            $totalRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('amount');

            $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
            $completedOrders = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count();

            $totalRooms = Room::where('is_active', true)->count();
            $occupiedRooms = Room::where('is_active', true)->where('status', 'occupied')->count();

            $activeStaff = Waiter::where('status', 'active')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'dates' => ['start' => $startDate, 'end' => $endDate],
                    'revenue' => [
                        'total' => $totalRevenue,
                        'formatted' => number_format($totalRevenue, 2),
                    ],
                    'orders' => [
                        'total' => $totalOrders,
                        'completed' => $completedOrders,
                        'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0,
                    ],
                    'occupancy' => [
                        'total_rooms' => $totalRooms,
                        'occupied' => $occupiedRooms,
                        'rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0,
                    ],
                    'staff' => [
                        'active' => $activeStaff,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate summary report: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getRevenueOverTime($startDate, $endDate, $groupBy)
    {
        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    private function getOccupancyOverTime($startDate, $endDate)
    {
        return CheckIn::whereBetween('check_in_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(check_in_date) as date'),
                DB::raw('COUNT(*) as check_ins')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getPeriodDates($period)
    {
        return match($period) {
            'today' => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            default => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
        };
    }
}

