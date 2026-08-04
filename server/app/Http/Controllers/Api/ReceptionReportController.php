<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceptionReportController extends Controller
{
    /**
     * Get reservation report with filters
     */
    public function reservationReport(Request $request): JsonResponse
    {
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
        
        // Group by date
        $dailyStats = $reservations->groupBy(function($item) {
            return Carbon::parse($item->created_at)->format('Y-m-d');
        })->map(function($group) {
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
     * Get occupancy report
     */
    public function occupancyReport(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        
        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        
        // Daily occupancy
        $period = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $dailyOccupancy = [];
        
        while ($period->lte($end)) {
            $checkIns = CheckIn::whereDate('checked_in_at', $period)
                ->whereNull('checked_out_at')
                ->count();
            
            $dailyOccupancy[] = [
                'date' => $period->format('Y-m-d'),
                'occupied' => $checkIns,
                'available' => $totalRooms - $checkIns,
                'occupancy_rate' => $totalRooms > 0 ? round(($checkIns / $totalRooms) * 100, 2) : 0,
            ];
            
            $period->addDay();
        }
        
        // Average occupancy
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
     * Get guest report
     */
    public function guestReport(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        
        $newGuests = Guest::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalGuests = Guest::count();
        
        // Top guests by reservations
        $topGuests = Guest::withCount(['reservations' => function($query) use ($startDate, $endDate) {
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
     * Get revenue report
     */
    public function revenueReport(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        
        // Get all verified payments in period
        $payments = Payment::where('status', 'verified')
            ->whereBetween('verified_at', [$startDate, $endDate])
            ->get();
        
        $totalRevenue = $payments->sum('amount');
        $reservationRevenue = $payments->whereNotNull('reservation_id')->sum('amount');
        $orderRevenue = $payments->whereNotNull('order_id')->sum('amount');
        
        // Daily revenue
        $dailyRevenue = $payments->groupBy(function($item) {
            return Carbon::parse($item->verified_at)->format('Y-m-d');
        })->map(function($group) {
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
     * Get check-in/check-out report
     */
    public function checkInOutReport(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        
        $checkIns = CheckIn::whereBetween('checked_in_at', [$startDate, $endDate])->count();
        $checkOuts = CheckIn::whereBetween('checked_out_at', [$startDate, $endDate])->count();
        $activeGuests = CheckIn::whereNull('checked_out_at')->count();
        
        // Daily stats
        $period = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $dailyStats = [];
        
        while ($period->lte($end)) {
            $dailyCheckIns = CheckIn::whereDate('checked_in_at', $period)->count();
            $dailyCheckOuts = CheckIn::whereDate('checked_out_at', $period)->count();
            
            $dailyStats[] = [
                'date' => $period->format('Y-m-d'),
                'check_ins' => $dailyCheckIns,
                'check_outs' => $dailyCheckOuts,
            ];
            
            $period->addDay();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_check_ins' => $checkIns,
                    'total_check_outs' => $checkOuts,
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
