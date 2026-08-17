<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationCollection;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with([
            'guest',
            'room.roomType',
            'creator'
        ]);

        // Search by booking reference, guest info, room number, or status
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('booking_reference', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%")
                  ->orWhereHas('guest', function ($g) use ($search) {
                      $g->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('room', function ($r) use ($search) {
                      $r->where('room_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', strtolower(trim($request->status)));
        }

        // Filter by room
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by guest
        if ($request->filled('guest_id')) {
            $query->where('guest_id', $request->guest_id);
        }

        // Filter by check-in date
        $checkInDate = $request->input('check_in_date') ?: $request->input('start_date');
        if (!empty($checkInDate)) {
            $query->whereDate('check_in_date', '>=', $checkInDate);
        }

        // Filter by check-out date
        $checkOutDate = $request->input('check_out_date') ?: $request->input('end_date');
        if (!empty($checkOutDate)) {
            $query->whereDate('check_out_date', '<=', $checkOutDate);
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
            $data = $request->validated();
            $guestId = $data['guest_id'] ?? null;

            // Automatically resolve or create guest if guest_id is not provided directly
            if (!$guestId) {
                $email = !empty($data['email']) ? strtolower(trim($data['email'])) : null;
                $phone = !empty($data['phone']) ? trim($data['phone']) : null;
                $existingGuest = null;

                if ($email) {
                    $existingGuest = \App\Models\Guest::where('email', $email)->first();
                }

                if (!$existingGuest && $phone) {
                    $existingGuest = \App\Models\Guest::where('phone', $phone)->first();
                }

                if ($existingGuest) {
                    $guestId = $existingGuest->id;
                    $existingGuest->update(array_filter([
                        'first_name' => $data['first_name'] ?? $existingGuest->first_name,
                        'last_name'  => $data['last_name'] ?? $existingGuest->last_name,
                        'phone'      => $phone ?? $existingGuest->phone,
                    ]));
                } else {
                    $newGuest = \App\Models\Guest::create([
                        'first_name' => $data['first_name'] ?? 'Guest',
                        'last_name'  => $data['last_name'] ?? 'Booking',
                        'email'      => $email,
                        'phone'      => $phone ?? 'N/A',
                    ]);
                    $guestId = $newGuest->id;
                }
            }

            $reservationData = array_merge($data, ['guest_id' => $guestId]);

            $reservation = Reservation::create($reservationData);

            // Load relations for complete reservation data
            $reservation->load(['guest', 'room', 'creator']);

            // Create notification for receptionist staff
            $this->createReservationNotification($reservation);

            DB::commit();

            return response()->json([
                'message' => 'Reservation created successfully.',
                'data' => new ReservationResource($reservation)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Reservation creation failed.',
                'error' => $e->getMessage()
            ], 500);
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
        try {
            Log::info('🗑️ [RESERVATION DELETE] Starting deletion process', [
                'reservation_id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'status' => $reservation->status,
                'has_checkin' => $reservation->checkIn !== null,
            ]);

            // Use database transaction for atomic deletion
            DB::beginTransaction();

            try {
                // Delete CheckIn record if it exists
                if ($reservation->checkIn) {
                    Log::info('🔍 [RESERVATION DELETE] CheckIn record found, deleting it first', [
                        'reservation_id' => $reservation->id,
                        'checkin_id' => $reservation->checkIn->id,
                    ]);
                    $reservation->checkIn()->delete();
                    Log::info('✅ [RESERVATION DELETE] CheckIn record deleted successfully');
                }

                // Disassociate orders linked to this reservation
                DB::table('orders')->where('reservation_id', $reservation->id)->update(['reservation_id' => null]);

                // Disassociate delivery tasks linked to this reservation if table exists
                if (Schema::hasTable('delivery_tasks')) {
                    DB::table('delivery_tasks')->where('reservation_id', $reservation->id)->update(['reservation_id' => null]);
                }

                // Disassociate payments linked to this reservation if table exists
                if (Schema::hasTable('payments')) {
                    DB::table('payments')->where('reservation_id', $reservation->id)->update(['reservation_id' => null]);
                }

                // Update room status to available if room is attached to this reservation
                if ($reservation->room) {
                    $oldStatus = $reservation->room->status;
                    $reservation->room->update([
                        'status' => 'available',
                    ]);
                    Log::info('✅ [RESERVATION DELETE] Room status updated', [
                        'room_id' => $reservation->room->id,
                        'room_number' => $reservation->room->room_number,
                        'old_status' => $oldStatus,
                        'new_status' => 'available',
                    ]);
                }

                // Delete the reservation
                $reservation->delete();
                Log::info('✅ [RESERVATION DELETE] Reservation deleted successfully', [
                    'reservation_id' => $reservation->id,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation deleted successfully.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('❌ [RESERVATION DELETE] Failed to delete reservation', [
                'reservation_id' => $reservation->id ?? 'unknown',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reservation. Please contact support if this persists.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
                Log::info(' [RESERVATION] CheckIn record created for reservation', [
                    'reservation_id' => $reservation->id,
                ]);
            }
        });
        // Send check-in email AFTER transaction (outside transaction scope)
        try {
            // Ensure room and room type relationships are loaded
            $reservation->load(['room', 'room.roomType', 'guest']);
            
            Log::info('📧 [RESERVATION] Preparing to send check-in email', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
            ]);
            
            Mail::to($reservation->guest->email)
                ->send(new \App\Mail\CheckInConfirmed($reservation));
            
            Log::info('📧 [RESERVATION] Check-in email sent successfully', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('📧 [RESERVATION] FAILED to send check-in email', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
        }

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

        // Update reservation status and create notifications
        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'confirmed',
            ]);

            // Create notification for receptionist dashboard
            $this->createConfirmationNotification($reservation);
        });

        // Send confirmation email ONLY to the target guest (reservation guest)
        try {
            // Ensure room and room type relationships are loaded
            $reservation->load(['room', 'room.roomType', 'guest']);
            
            Log::info('📧 [RESERVATION] Preparing to send confirmation email', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
                'guest_name' => $reservation->guest->first_name . ' ' . $reservation->guest->last_name,
                'queue_connection' => config('queue.default'),
                'mail_driver' => config('mail.default'),
            ]);
            
            Mail::to($reservation->guest->email)
                ->send(new \App\Mail\ReservationConfirmed($reservation));
            
            Log::info('📧 [RESERVATION] Confirmation email sent successfully', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
                'guest_name' => "{$reservation->guest->first_name} {$reservation->guest->last_name}",
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('📧 [RESERVATION] FAILED to send confirmation email', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
        }

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
            // Log before update
            Log::info('🔍 [RESERVATION CHECKOUT] Starting checkout', [
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'room_number' => $reservation->room->room_number,
                'room_status_before' => $reservation->room->status,
            ]);

            // Update reservation status
            $reservation->update([
                'status' => 'checked_out',
            ]);

            // Update room status to available
            $reservation->room()->update([
                'status' => 'available',
            ]);

            // Verify room status was updated
            $room = $reservation->room->fresh();
            Log::info('✅ [RESERVATION CHECKOUT] Room status updated', [
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_status_after' => $room->status,
                'verified' => $room->status === 'available' ? 'YES' : 'NO',
            ]);
            
            // Update CheckIn record if exists
            if ($reservation->checkIn) {
                $reservation->checkIn()->update([
                    'checked_out_at' => now(),
                ]);
                Log::info('✅ [RESERVATION] CheckIn record updated for checkout', [
                    'reservation_id' => $reservation->id,
                ]);
            }
        });

        // Send check-out email AFTER transaction (outside transaction scope)
        try {
            // Ensure room and room type relationships are loaded
            $reservation->load(['room', 'room.roomType', 'guest']);
            
            Log::info('📧 [RESERVATION] Preparing to send check-out email', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
            ]);
            
            Mail::to($reservation->guest->email)
                ->send(new \App\Mail\CheckOutNotification($reservation));
            
            Log::info('📧 [RESERVATION] Check-out email sent successfully', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('📧 [RESERVATION] FAILED to send check-out email', [
                'reservation_id' => $reservation->id,
                'guest_email' => $reservation->guest->email,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
        }

        Log::info('🎉 [RESERVATION CHECKOUT] Checkout completed successfully', [
            'reservation_id' => $reservation->id,
            'room_status' => $reservation->fresh('room')->room->status,
        ]);

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
                        'room_type' => $reservation->room->roomType?->name ?? 'Unknown',
                        'check_in_date' => $reservation->check_in_date,
                        'check_out_date' => $reservation->check_out_date,
                    ]
                );
            }

            Log::info('Notifications created for new reservation', [
                'reservation_id' => $reservation->id,
                'receptionists_count' => count($receptionists),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating reservation notification: ' . $e->getMessage());
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
                        'room_type' => $reservation->room->roomType?->name ?? 'Unknown',
                        'check_in_date' => $reservation->check_in_date,
                        'check_out_date' => $reservation->check_out_date,
                    ]
                );
            }

            Log::info('✓ [RESERVATION] Confirmation notifications created', [
                'reservation_id' => $reservation->id,
                'receptionists_count' => count($receptionists),
            ]);
        } catch (\Exception $e) {
            Log::error('✗ [RESERVATION] Error creating confirmation notification: ' . $e->getMessage());
        }
    }
}
