<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    
    public function index(Request $request): JsonResponse
    {
        $hotelId = $request->input('hotel_id')
            ?: $request->header('X-Hotel-ID')
            ?: TenantContext::id();

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        $today = Carbon::today();

        $statistics = [
            'today_check_ins' => CheckIn::whereDate('checked_in_at', $today)->count(),
            'today_check_outs' => CheckIn::whereDate('checked_out_at', $today)->count(),
            'checkout_count' => CheckIn::whereDate('checked_out_at', $today)->count(),
            'active_guests' => CheckIn::whereNull('checked_out_at')->count(),
            'available_rooms' => Room::where('status', 'available')->count(),
            'pending_reservations' => Reservation::where('status', 'pending')->count(),
            'confirmed_reservations' => Reservation::where('status', 'confirmed')->count(),
        ];

        $todayArrivals = Reservation::with(['guest', 'room.roomType'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereDate('check_in_date', '<=', $today)
            ->whereDoesntHave('checkIn')
            ->orderBy('check_in_date', 'asc')
            ->get();

        $todayDepartures = CheckIn::with(['guest', 'room.roomType'])
            ->whereDate('expected_check_out_at', '<=', $today)
            ->whereNull('checked_out_at')
            ->orderBy('expected_check_out_at', 'asc')
            ->get();

        $roomMatrix = Room::with('roomType')
            ->select([
                'id',
                'room_number',
                'floor',
                'status',
                'room_type_id',
            ])
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        $recentGuests = Guest::latest()
            ->take(10)
            ->get();

        $recentReservations = Reservation::with(['guest', 'room'])
            ->latest()
            ->take(10)
            ->get();

        $activeCheckIns = CheckIn::with(['guest', 'room'])
            ->whereNull('checked_out_at')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'today_arrivals' => $todayArrivals,
            'today_departures' => $todayDepartures,
            'room_matrix' => $roomMatrix,
            'recent_guests' => $recentGuests,
            'recent_reservations' => $recentReservations,
            'active_check_ins' => $activeCheckIns,
        ]);
    }
}