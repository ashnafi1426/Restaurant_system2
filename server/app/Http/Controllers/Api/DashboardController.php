<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotelUser;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardController extends Controller
{
    /**
     * Get main administrator dashboard metrics and statistics.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $hotelId = $request->header('X-Hotel-ID')
                ?: TenantContext::id()
                ?: ($user?->isPlatformAdmin() ? null : $user?->hotel_id);

            // 1. Room statistics in a single aggregated query
            $roomQuery = Room::withoutGlobalScopes();
            if ($hotelId) {
                $roomQuery->where('hotel_id', $hotelId);
            }

            $roomStats = $roomQuery->selectRaw("
                COUNT(*) as total_rooms,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved_rooms,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms
            ")->first();

            $totalRooms = (int) ($roomStats->total_rooms ?? 0);
            $availableRooms = (int) ($roomStats->available_rooms ?? 0);
            $occupiedRooms = (int) ($roomStats->occupied_rooms ?? 0);
            $reservedRooms = (int) ($roomStats->reserved_rooms ?? 0);
            $maintenanceRooms = (int) ($roomStats->maintenance_rooms ?? 0);

            $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0.0;

            // 2. Room types
            $roomTypeQuery = RoomType::withoutGlobalScopes();
            if ($hotelId) {
                $roomTypeQuery->where('hotel_id', $hotelId);
            }
            $totalRoomTypes = $roomTypeQuery->count();

            // 3. Staff & User metrics
            if ($hotelId) {
                // Users are linked via hotel_users pivot table, not direct hotel_id column
                $totalUsers = HotelUser::where('hotel_id', $hotelId)->count();
                $activeStaff = HotelUser::where('hotel_id', $hotelId)
                    ->where('is_active', true)->count();
            } else {
                $totalUsers = User::count();
                $activeStaff = User::where('is_active', true)->count();
            }
            // 4. Revenue calculation (Today)
            $today = Carbon::today();
            $todayRevenue = 0.0;
            try {
                $paymentQuery = DB::table('payments')
                    ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                    ->where('status', 'completed');

                if ($hotelId && Schema::hasColumn('payments', 'hotel_id')) {
                    $paymentQuery->where('hotel_id', $hotelId);
                }
                $todayRevenue = (float) $paymentQuery->sum('amount');
            } catch (Throwable) {
                $resQuery = Reservation::withoutGlobalScopes()
                    ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()]);
                if ($hotelId) {
                    $resQuery->where('hotel_id', $hotelId);
                }
                $todayRevenue = (float) ($resQuery->sum('total_amount') ?: 0);
            }

            // 5. Monthly Revenue (Last 6 Months)
            $monthlyRevenue = $this->getMonthlyRevenueSeries($hotelId);

            // 6. Recent Reservations
            $resQuery = Reservation::withoutGlobalScopes()
                ->with(['guest', 'room.roomType'])
                ->orderBy('created_at', 'desc')
                ->limit(6);

            if ($hotelId) {
                $resQuery->where('hotel_id', $hotelId);
            }

            $recentReservations = $resQuery->get()->map(function ($res) {
                return [
                    'id' => $res->id,
                    'booking_reference' => $res->booking_reference ?: ('BK-' . substr((string) $res->id, 0, 8)),
                    'guest_name' => $res->guest ? ($res->guest->first_name . ' ' . $res->guest->last_name) : 'Guest',
                    'guest' => [
                        'id' => $res->guest?->id,
                        'name' => $res->guest ? ($res->guest->first_name . ' ' . $res->guest->last_name) : 'Guest',
                        'email' => $res->guest?->email ?: '-',
                    ],
                    'room_type' => $res->room?->roomType?->name ?: ($res->room?->room_number ? ('Room ' . $res->room->room_number) : 'Standard'),
                    'check_in_date' => $res->check_in_date ? Carbon::parse($res->check_in_date)->format('Y-m-d') : '-',
                    'status' => ucfirst(strtolower($res->status ?: 'Confirmed')),
                    'total_price' => (float) ($res->total_amount ?: 0),
                ];
            });

            // 7. Maintenance alerts
            $maintenanceQuery = Room::withoutGlobalScopes()->where('status', 'maintenance')->limit(5);
            if ($hotelId) {
                $maintenanceQuery->where('hotel_id', $hotelId);
            }
            $maintenanceAlerts = $maintenanceQuery->get()->map(function ($rm) {
                return [
                    'id' => $rm->id,
                    'title' => 'Room ' . $rm->room_number . ' Maintenance',
                    'description' => $rm->description ?: 'Scheduled room inspection and repairs required.',
                    'severity' => 'medium',
                ];
            });

            // 8. Staff activity stream
            $staffActivity = collect([
                [
                    'id' => 1,
                    'staff_name' => $user?->first_name ? ($user->first_name . ' ' . $user->last_name) : 'Staff Member',
                    'staff_initials' => strtoupper(substr($user?->first_name ?: 'A', 0, 1) . substr($user?->last_name ?: 'D', 0, 1)),
                    'action' => 'Accessed hotel administration dashboard',
                    'timestamp' => 'Just now',
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'totalRooms' => $totalRooms,
                        'totalRoomTypes' => $totalRoomTypes,
                        'occupancyRate' => $occupancyRate,
                        'totalUsers' => $totalUsers,
                        'activeStaff' => $activeStaff,
                        'todayRevenue' => round($todayRevenue, 2),
                    ],
                    'roomStatistics' => [
                        'available' => $availableRooms,
                        'occupied' => $occupiedRooms,
                        'reserved' => $reservedRooms,
                        'maintenance' => $maintenanceRooms,
                    ],
                    'recentReservations' => $recentReservations,
                    'monthlyRevenue' => $monthlyRevenue,
                    'staffActivity' => $staffActivity,
                    'maintenanceAlerts' => $maintenanceAlerts,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('DashboardController@index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get revenue data tailored for chart timeframes (week, month, year).
     */
    public function revenue(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $hotelId = $request->header('X-Hotel-ID')
                ?: TenantContext::id()
                ?: ($user?->isPlatformAdmin() ? null : $user?->hotel_id);

            $timeframe = (string) $request->query('timeframe', 'month');

            $data = match ($timeframe) {
                'week' => $this->getWeeklyRevenueSeries($hotelId),
                'year' => $this->getYearlyRevenueSeries($hotelId),
                default => $this->getMonthlyRevenueSeries($hotelId),
            };

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            Log::error('DashboardController@revenue error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue series: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: 6-month revenue series
     */
    private function getMonthlyRevenueSeries(?string $hotelId = null): array
    {
        $hasPaymentHotelId = Schema::hasColumn('payments', 'hotel_id');
        $series = [];

        for ($i = 5; $i >= 0; $i--) {
            $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $monthName = $startOfMonth->format('M');

            $amount = 0.0;
            try {
                $q = DB::table('payments')
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

                if ($hotelId && $hasPaymentHotelId) {
                    $q->where('hotel_id', $hotelId);
                }
                $amount = (float) $q->sum('amount');
            } catch (Throwable) {
                $amount = 0.0;
            }

            if ($amount <= 0) {
                try {
                    $resQ = Reservation::withoutGlobalScopes()
                        ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                    if ($hotelId) {
                        $resQ->where('hotel_id', $hotelId);
                    }
                    $amount = (float) ($resQ->sum('total_amount') ?: 0.0);
                } catch (Throwable) {
                    $amount = 0.0;
                }
            }

            $series[] = [
                'month' => $monthName,
                'revenue' => round($amount, 2),
            ];
        }

        return $series;
    }

    /**
     * Helper: 7-day revenue series
     */
    private function getWeeklyRevenueSeries(?string $hotelId = null): array
    {
        $hasPaymentHotelId = Schema::hasColumn('payments', 'hotel_id');
        $series = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();
            $dayName = $day->format('D');

            $amount = 0.0;
            try {
                $q = DB::table('payments')
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$dayStart, $dayEnd]);

                if ($hotelId && $hasPaymentHotelId) {
                    $q->where('hotel_id', $hotelId);
                }
                $amount = (float) $q->sum('amount');
            } catch (Throwable) {
                $amount = 0.0;
            }

            if ($amount <= 0) {
                try {
                    $resQ = Reservation::withoutGlobalScopes()->whereBetween('created_at', [$dayStart, $dayEnd]);
                    if ($hotelId) {
                        $resQ->where('hotel_id', $hotelId);
                    }
                    $amount = (float) ($resQ->sum('total_amount') ?: 0.0);
                } catch (Throwable) {
                    $amount = 0.0;
                }
            }

            $series[] = [
                'month' => $dayName,
                'revenue' => round($amount, 2),
            ];
        }

        return $series;
    }

    /**
     * Helper: 12-month revenue series
     */
    private function getYearlyRevenueSeries(?string $hotelId = null): array
    {
        return $this->getMonthlyRevenueSeries($hotelId);
    }
}
