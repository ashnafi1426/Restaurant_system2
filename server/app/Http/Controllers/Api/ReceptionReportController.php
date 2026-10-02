<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceptionReportController extends Controller
{
    /**
     * Resolve and apply active tenant context.
     */
    private function resolveTenant(Request $request): ?string
    {
        $hotelId = $request->input('hotel_id')
            ?: $request->header('X-Hotel-ID')
            ?: TenantContext::id();

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    /**
     * Generate reservation metrics report over the selected date range.
     */
    public function reservationReport(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $reservations = Reservation::with(['guest', 'room'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $summary = [
            'total' => $reservations->count(),
            'pending' => $reservations->where('status', 'pending')->count(),
            'confirmed' => $reservations->where('status', 'confirmed')->count(),
            'checked_in' => $reservations->where('status', 'checked_in')->count(),
            'checked_out' => $reservations->where('status', 'checked_out')->count(),
            'cancelled' => $reservations->where('status', 'cancelled')->count(),
        ];

        $dailyStats = $reservations->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('Y-m-d'),
                'count' => $group->count(),
                'pending' => $group->where('status', 'pending')->count(),
                'confirmed' => $group->where('status', 'confirmed')->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'daily_stats' => $dailyStats,
                'reservations' => $reservations,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Generate property occupancy metrics over the selected date range.
     */
    public function occupancyReport(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();

        $activeCheckIns = CheckIn::whereBetween('checked_in_at', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->whereNull('checked_out_at')
            ->get(['id', 'checked_in_at']);

        $dailyCounts = $activeCheckIns->groupBy(function ($item) {
            return Carbon::parse($item->checked_in_at)->format('Y-m-d');
        })->map->count();

        $period = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $dailyOccupancy = [];

        while ($period->lte($end)) {
            $dateKey = $period->format('Y-m-d');
            $checkInsCount = $dailyCounts->get($dateKey, 0);

            $dailyOccupancy[] = [
                'date' => $dateKey,
                'occupied' => $checkInsCount,
                'available' => max(0, $totalRooms - $checkInsCount),
                'occupancy_rate' => $totalRooms > 0 ? round(($checkInsCount / $totalRooms) * 100, 2) : 0,
            ];

            $period->addDay();
        }

        $avgOccupancyRate = count($dailyOccupancy) > 0
            ? round(collect($dailyOccupancy)->avg('occupancy_rate'), 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_rooms' => $totalRooms,
                    'available' => $availableRooms,
                    'occupied' => $occupiedRooms,
                    'avg_occupancy_rate' => $avgOccupancyRate,
                ],
                'daily_occupancy' => $dailyOccupancy,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Generate guest count and frequent guest report over the selected date range.
     */
    public function guestReport(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $newGuests = Guest::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalGuests = Guest::count();

        $topGuests = Guest::withCount(['reservations' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
            ->having('reservations_count', '>', 0)
            ->orderByDesc('reservations_count')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_guests' => $totalGuests,
                    'new_guests' => $newGuests,
                ],
                'top_guests' => $topGuests,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Generate revenue breakdown report from verified payments.
     */
    public function revenueReport(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $payments = Payment::where('status', 'verified')
            ->whereBetween('verified_at', [$startDate, $endDate])
            ->get();

        $totalRevenue = $payments->sum('amount');
        $reservationRevenue = $payments->whereNotNull('reservation_id')->sum('amount');
        $orderRevenue = $payments->whereNotNull('order_id')->sum('amount');

        $dailyRevenue = $payments->groupBy(function ($item) {
            return Carbon::parse($item->verified_at)->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => Carbon::parse($group->first()->verified_at)->format('Y-m-d'),
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'reservation_revenue' => $reservationRevenue,
                    'order_revenue' => $orderRevenue,
                    'payment_count' => $payments->count(),
                ],
                'daily_revenue' => $dailyRevenue,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Generate check-in and check-out activity report over the selected date range.
     */
    public function checkInOutReport(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $checkInsTotal = CheckIn::whereBetween('checked_in_at', [$startDate, $endDate])->count();
        $checkOutsTotal = CheckIn::whereBetween('checked_out_at', [$startDate, $endDate])->count();
        $activeGuests = CheckIn::whereNull('checked_out_at')->count();

        $checkInRecords = CheckIn::whereBetween('checked_in_at', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->get(['id', 'checked_in_at']);
        $checkOutRecords = CheckIn::whereBetween('checked_out_at', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->get(['id', 'checked_out_at']);

        $checkInsByDay = $checkInRecords->groupBy(function ($item) {
            return Carbon::parse($item->checked_in_at)->format('Y-m-d');
        })->map->count();

        $checkOutsByDay = $checkOutRecords->groupBy(function ($item) {
            return Carbon::parse($item->checked_out_at)->format('Y-m-d');
        })->map->count();

        $period = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $dailyStats = [];

        while ($period->lte($end)) {
            $dateKey = $period->format('Y-m-d');

            $dailyStats[] = [
                'date' => $dateKey,
                'check_ins' => $checkInsByDay->get($dateKey, 0),
                'check_outs' => $checkOutsByDay->get($dateKey, 0),
            ];

            $period->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_check_ins' => $checkInsTotal,
                    'total_check_outs' => $checkOutsTotal,
                    'active_guests' => $activeGuests,
                ],
                'daily_stats' => $dailyStats,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
        ]);
    }
}
