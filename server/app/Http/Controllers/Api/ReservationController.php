<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationCollection;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class ReservationController extends Controller
{
    public function index(Request $request)
       {
        $query = Reservation::with([
        'guest',
        'room',
        'creator'
       ]);

      if ($request->filled('search')) {
         $query->search($request->search);
        }

       if ($request->filled('status')) {
        $query->where('status', $request->status);
        }

       if ($request->filled('room_id')) {
        $query->where('room_id', $request->room_id);
        }

        if ($request->filled('guest_id')) {
        $query->where('guest_id', $request->guest_id);
    }

    $reservations = $query
        ->latest()
        ->paginate(
            $request->integer('per_page', 10)
        );

       return new ReservationCollection($reservations);
    }
    public function store(StoreReservationRequest $request)
     {
     DB::beginTransaction();

     try {

        $reservation = Reservation::create(
            $request->validated()
        );

        // Load relations for complete reservation data
        $reservation->load(['guest', 'room', 'creator']);

        // Create notification for receptionist staff
        $this->createReservationNotification($reservation);

        DB::commit();

        return response()->json([

            'message' =>
                'Reservation created successfully.',

            'data' =>
                new ReservationResource($reservation)

        ],201);

    } catch (\Exception $e){

        DB::rollBack();

        return response()->json([
            'message'=>'Reservation creation failed.',
            'error'=>$e->getMessage()
        ],500);

    }
     }
    public function show(Reservation $reservation)
    {
    $reservation->load([
        'guest',
        'room',
        'creator'
    ]);

    return new ReservationResource($reservation);
 }
   public function update(
    UpdateReservationRequest $request,Reservation $reservation)
    {
     DB::transaction(function () use (
        $reservation,
        $request
       )
    {

        $reservation->update(
            $request->validated()
        );

    });

    return response()->json([

        'message'=>'Reservation updated.',

        'data'=>new ReservationResource(
            $reservation->fresh([
                'guest',
                'room',
                'creator'
            ])
        )

    ]);
    }
    public function destroy(Reservation $reservation)
    {
    $reservation->delete();

    return response()->json([
        'message'=>'Reservation deleted.'
    ]);
    }
    public function checkIn(Reservation $reservation)
    {
    if(!$reservation->canCheckIn()){

        return response()->json([
            'message'=>'Reservation cannot be checked in.'
        ],422);
    }
    DB::transaction(function() use($reservation){
        // Update reservation status
        $reservation->update([
            'status'=>'checked_in'
        ]);
        
        // Update room status
        $reservation->room()->update([
            'status'=>'occupied'
        ]);
        
        // Create CheckIn record if not exists
        if (!$reservation->checkIn) {
            \App\Models\CheckIn::create([
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'room_id' => $reservation->room_id,
                'checked_in_at' => now(),
                'expected_check_out_at' => $reservation->check_out_date,
            ]);
            \Log::info(' [RESERVATION] CheckIn record created for reservation', [
                'reservation_id' => $reservation->id,
            ]);
        }

        // Send check-in email to guest
        try {
            \Mail::to($reservation->guest->email)
                ->send(new \App\Mail\CheckInConfirmed($reservation));
            
            \Log::info('📧 [RESERVATION] Check-in email sent', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
            ]);
        } catch (\Exception $e) {
            \Log::error('📧 [RESERVATION] Failed to send check-in email: ' . $e->getMessage());
        }
    });
    return new ReservationResource(
        $reservation->fresh([
            'guest',
            'room'
        ])
    );
    }

    public function confirm(Reservation $reservation)
    {
        if ($reservation->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending reservations can be confirmed.',
            ], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'confirmed',
            ]);

            // Send confirmation email to guest
            try {
                \Mail::to($reservation->guest->email)
                    ->send(new \App\Mail\ReservationConfirmed($reservation));
                
                \Log::info('📧 [RESERVATION] Confirmation email sent', [
                    'reservation_id' => $reservation->id,
                    'guest_email' => $reservation->guest->email,
                ]);
            } catch (\Exception $e) {
                \Log::error('📧 [RESERVATION] Failed to send confirmation email: ' . $e->getMessage());
            }

            // Create notification for receptionist dashboard
            $this->createConfirmationNotification($reservation);
        });

        return new ReservationResource(
            $reservation->fresh([
                'guest',
                'room',
                'creator',
            ])
        );
    }

    public function checkOut(Reservation $reservation)
    {
        if ($reservation->status !== 'checked_in') {
            return response()->json([
                'message' => 'Only checked-in reservations can be checked out.',
            ], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'checked_out',
            ]);
            $reservation->room()->update([
                'status' => 'available',
            ]);
            
            // Update CheckIn record if exists
            if ($reservation->checkIn) {
                $reservation->checkIn()->update([
                    'checked_out_at' => now(),
                ]);
                \Log::info(' [RESERVATION] CheckIn record updated for checkout', [
                    'reservation_id' => $reservation->id,
                ]);
            }

            // Send check-out email to guest
            try {
                \Mail::to($reservation->guest->email)
                    ->send(new \App\Mail\CheckOutNotification($reservation));
                
                \Log::info('📧 [RESERVATION] Check-out email sent', [
                    'reservation_id' => $reservation->id,
                    'guest_email' => $reservation->guest->email,
                ]);
            } catch (\Exception $e) {
                \Log::error('📧 [RESERVATION] Failed to send check-out email: ' . $e->getMessage());
            }
        });

        return new ReservationResource(
            $reservation->fresh([
                'guest',
                'room',
                'creator',
            ])
        );
    }

    public function cancel(Reservation $reservation)
    {
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'Only pending or confirmed reservations can be cancelled.',
            ], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        });

        return new ReservationResource(
            $reservation->fresh([
                'guest',
                'room',
                'creator',
            ])
        );
    }

    /**
     * Create notification when a new reservation is booked
     */
    private function createReservationNotification(Reservation $reservation)
    {
        try {
            // Get all receptionist users to notify
            $receptionists = \App\Models\User::where('role', 'receptionist')->get();

            foreach ($receptionists as $receptionist) {
                NotificationController::createNotification(
                    $receptionist->id,
                    'booking',
                    'New Booking',
                    'A new reservation has been made.',
                    [
                        'reservation_id' => $reservation->id,
                        'guest_name' => $reservation->guest->first_name . ' ' . $reservation->guest->last_name,
                        'room_number' => $reservation->room->room_number,
                        'room_type' => $reservation->room->room_type->name ?? 'Unknown',
                        'check_in_date' => $reservation->check_in_date,
                        'check_out_date' => $reservation->check_out_date,
                    ]
                );
            }

            \Log::info('Notifications created for new reservation', [
                'reservation_id' => $reservation->id,
                'receptionists_count' => count($receptionists),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating reservation notification: ' . $e->getMessage());
        }
    }

    /**
     * Create notification when reservation is confirmed
     */
    private function createConfirmationNotification(Reservation $reservation)
    {
        try {
            // Get all receptionist users to notify
            $receptionists = \App\Models\User::where('role', 'receptionist')->get();

            foreach ($receptionists as $receptionist) {
                NotificationController::createNotification(
                    $receptionist->id,
                    'booking',
                    'Reservation Confirmed',
                    'A reservation has been confirmed and confirmation email sent to guest.',
                    [
                        'reservation_id' => $reservation->id,
                        'guest_name' => $reservation->guest->first_name . ' ' . $reservation->guest->last_name,
                        'room_number' => $reservation->room->room_number,
                        'room_type' => $reservation->room->room_type->name ?? 'Unknown',
                        'check_in_date' => $reservation->check_in_date,
                        'check_out_date' => $reservation->check_out_date,
                    ]
                );
            }

            \Log::info('✓ [RESERVATION] Confirmation notifications created', [
                'reservation_id' => $reservation->id,
                'receptionists_count' => count($receptionists),
            ]);
        } catch (\Exception $e) {
            \Log::error('✗ [RESERVATION] Error creating confirmation notification: ' . $e->getMessage());
        }
    }
}
