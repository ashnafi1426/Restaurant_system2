<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\CheckIn;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckInRequest;
use App\Http\Resources\CheckInResource;

class CheckInController extends Controller
{
    public function index(Request $request)
    {
        \Log::info(' [CHECK-IN] Index called', [
            'params' => $request->all(),
            'per_page' => $request->integer('per_page', 10),
        ]);

        $query = CheckIn::with([
            'guest',
            'room',
            'reservation',
        ]);

        \Log::info(' [CHECK-IN] Total check-ins before filters', [
            'count' => $query->count(),
        ]);
        if ($request->filled('guest')) {
            $query->whereHas('guest', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->guest}%");
            });
            \Log::info(' [CHECK-IN] Applied guest filter', ['guest' => $request->guest]);
        }
        if ($request->filled('room')) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('room_number', 'like', "%{$request->room}%");
            });
            \Log::info('[CHECK-IN] Applied room filter', ['room' => $request->room]);
        }

        if ($request->filled('reservation')) {
            $query->whereHas('reservation', function ($q) use ($request) {
                $q->where(
                    'reservation_number',
                    'like',
                    "%{$request->reservation}%"
                );
            });
            \Log::info('[CHECK-IN] Applied reservation filter', ['reservation' => $request->reservation]);
        }

        $checkIns = $query
            ->latest('checked_in_at')
            ->paginate($request->integer('per_page', 10));

        \Log::info('[CHECK-IN] Paginated results', [
            'count' => $checkIns->count(),
            'total' => $checkIns->total(),
            'page' => $checkIns->currentPage(),
            'per_page' => $checkIns->perPage(),
        ]);

        return CheckInResource::collection($checkIns);
    }
    public function store(StoreCheckInRequest $request)
    {
        \Log::info('[CHECK-IN] POST request received', [
            'reservation_id' => $request->reservation_id,
            'request_data' => $request->all(),
        ]);

        DB::beginTransaction();

        try {

            $reservation = Reservation::with([
                'guest',
                'room',
            ])->findOrFail($request->reservation_id);

            \Log::info(' [CHECK-IN] Reservation found', [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'room_id' => $reservation->room_id,
                'room_status' => $reservation->room->status,
                'check_in_date' => $reservation->check_in_date,
                'check_out_date' => $reservation->check_out_date,
                'guest_id' => $reservation->guest_id,
                'room_details' => [
                    'number' => $reservation->room->room_number,
                    'is_active' => $reservation->room->is_active,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            \Log::info('[CHECK-IN] Starting validation checks');

            if ($reservation->status !== 'confirmed') {
                $msg = "Only confirmed reservations can be checked in. Status is: {$reservation->status}";
                \Log::error(' [CHECK-IN] Validation failed: reservation status', ['status' => $reservation->status, 'message' => $msg]);
                throw new Exception($msg);
            }
            \Log::info(' [CHECK-IN] Validation 1 passed: reservation status is confirmed');

            if ($reservation->room->status !== 'available') {
                $msg = "Selected room is not available. Room status is: {$reservation->room->status}";
                \Log::error(' [CHECK-IN] Validation failed: room status', ['room_status' => $reservation->room->status, 'message' => $msg]);
                throw new Exception($msg);
            }
            \Log::info(' [CHECK-IN] Validation 2 passed: room status is available');

            if (
                CheckIn::where(
                    'reservation_id',
                    $reservation->id
                )->exists()
            ) {
                $msg = 'This reservation has already been checked in.';
                \Log::error(' [CHECK-IN] Validation failed: already checked in', ['message' => $msg]);
                throw new Exception($msg);
            }
            \Log::info(' [CHECK-IN] Validation 3 passed: not already checked in');

            /*
            |--------------------------------------------------------------------------
            | Create Check In
            |--------------------------------------------------------------------------
            */

            \Log::info(' [CHECK-IN] All validations passed, creating check-in record');

            $checkIn = CheckIn::create([

                'reservation_id' => $reservation->id,

                'guest_id' => $reservation->guest_id,

                'room_id' => $reservation->room_id,

                'checked_in_at' => now(),

                'expected_check_out_at' => $reservation->check_out_date,

            ]);

            \Log::info(' [CHECK-IN] Check-in record created', [
                'check_in_id' => $checkIn->id,
                'checked_in_at' => $checkIn->checked_in_at,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Reservation
            |--------------------------------------------------------------------------
            */

            $reservation->update([
                'status' => 'checked_in',
            ]);

            \Log::info(' [CHECK-IN] Reservation status updated to checked_in');

            /*
            |--------------------------------------------------------------------------
            | Update Room
            |--------------------------------------------------------------------------
            */

            $reservation->room->update([
                'status' => 'occupied',
            ]);

            \Log::info(' [CHECK-IN] Room status updated to occupied');

            DB::commit();

            \Log::info(' [CHECK-IN] Transaction committed - check-in successful');

            return response()->json([

                'success' => true,

                'message' => 'Guest checked in successfully.',

                'data' => new CheckInResource(
                    $checkIn->load([
                        'guest',
                        'room',
                        'reservation',
                    ])
                ),

            ], 201);

        } catch (Exception $exception) {

            DB::rollBack();

            \Log::error(' [CHECK-IN] Exception caught, rolling back', [
                'error_message' => $exception->getMessage(),
                'error_file' => $exception->getFile(),
                'error_line' => $exception->getLine(),
            ]);

            return response()->json([

                'success' => false,

                'message' => $exception->getMessage(),

            ], 422);
        }
    }

    public function show(CheckIn $checkIn)
    {
        $checkIn->load([
            'guest',
            'room',
            'reservation',
        ]);

        return new CheckInResource($checkIn);
    }

    public function destroy(CheckIn $checkIn)
    {
        if ($checkIn->checked_out_at) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a completed check out.',
            ], 422);
        }

        $checkIn->delete();

        return response()->json([
            'success' => true,
            'message' => 'Check in deleted successfully.',
        ]);
    }

    public function checkout(CheckIn $checkIn)
    {
        DB::beginTransaction();

        try {
            if ($checkIn->checked_out_at) {
                throw new Exception('Guest already checked out.');
            }

            $checkIn->update([
                'checked_out_at' => now(),
            ]);

            $reservation = $checkIn->reservation;
            $reservation->update([
                'status' => 'checked_out',
            ]);

            $room = $checkIn->room;
            $room->update([
                'status' => 'available',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Guest checked out successfully.',
                'data' => new CheckInResource($checkIn->load(['guest', 'room', 'reservation'])),
            ], 200);
        } catch (Exception $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function statistics()
    {
        return response()->json([

            'total_check_ins' => CheckIn::count(),

            'today_check_ins' => CheckIn::whereDate(
                'checked_in_at',
                today()
            )->count(),

            'active_guests' => CheckIn::whereNull(
                'checked_out_at'
            )->count(),

            'expected_today' => CheckIn::whereDate(
                'expected_check_out_at',
                today()
            )->count(),
        ]);
    }
}