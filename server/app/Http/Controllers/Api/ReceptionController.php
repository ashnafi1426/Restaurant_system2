<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
// use App\Models\CheckOut;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ReceptionController extends Controller
{
    /**
     * Reception Dashboard
     */
    public function index(): JsonResponse
    {
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Statistics Cards
        |--------------------------------------------------------------------------
        */

        $statistics = [

            'today_check_ins' => CheckIn::whereDate('checked_in_at', $today)->count(),

            'today_check_outs' => CheckIn::whereDate('checked_out_at', $today)->count(),

            'checkout_count' => CheckIn::whereDate('checked_out_at', $today)->count(),

            'active_guests' => CheckIn::whereNull('checked_out_at')->count(),

            'available_rooms' => Room::where('status', 'available')->count(),

            'pending_reservations' => Reservation::where('status', 'pending')->count(),

            'confirmed_reservations' => Reservation::where('status', 'confirmed')->count(),

        ];

        /*
        |--------------------------------------------------------------------------
        | Today's Arrivals (Pending or Confirmed reservations due today or earlier not yet checked in)
        |--------------------------------------------------------------------------
        */

        $todayArrivals = Reservation::with([
                'guest',
                'room.roomType'
            ])
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereDate('check_in_date', '<=', $today)
            ->whereDoesntHave('checkIn')
            ->orderBy('check_in_date', 'asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Today's Departures (Active check-ins due for check-out today or earlier)
        |--------------------------------------------------------------------------
        */

        $todayDepartures = CheckIn::with([
                'guest',
                'room.roomType'
            ])
            ->whereDate('expected_check_out_at', '<=', $today)
            ->whereNull('checked_out_at')
            ->orderBy('expected_check_out_at', 'asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Room Status Matrix
        |--------------------------------------------------------------------------
        */

        $roomMatrix = Room::with('roomType')
            ->select([
                'id',
                'room_number',
                'floor',
                'status',
                'room_type_id'
            ])
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Guests
        |--------------------------------------------------------------------------
        */

        $recentGuests = Guest::latest()
            ->take(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Reservations
        |--------------------------------------------------------------------------
        */

        $recentReservations = Reservation::with([
                'guest',
                'room'
            ])
            ->latest()
            ->take(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Active Check Ins
        |--------------------------------------------------------------------------
        */

        $activeCheckIns = CheckIn::with([
                'guest',
                'room'
            ])
            ->whereNull('checked_out_at')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([

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