<?php

namespace App\Services;

use App\Mail\CheckInConfirmed;
use App\Mail\ReservationConfirmed;
use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * Get paginated reservations scoped to the active hotel tenant.
     */
    public function getReservations(array $filters = [], int $perPage = 10, ?string $hotelId = null): LengthAwarePaginator
    {
        $hotelId = $hotelId ?: TenantContext::id();

        $query = Reservation::select([
            'id', 'hotel_id', 'guest_id', 'room_id', 'booking_reference',
            'check_in_date', 'check_out_date', 'status', 'total_amount',
            'notes', 'created_by_id', 'created_at', 'updated_at'
        ])->with([
            'guest:id,first_name,last_name,email,phone,hotel_id',
            'room:id,room_number,room_type_id,status,hotel_id',
            'room.roomType:id,name,base_price_per_night,capacity',
            'creator:id,first_name,last_name,email',
            'checkIn:id,reservation_id,checked_in_at,checked_out_at'
        ]);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
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

        if (!empty($filters['status'])) {
            $query->where('status', strtolower(trim($filters['status'])));
        }

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['guest_id'])) {
            $query->where('guest_id', $filters['guest_id']);
        }

        $checkInDate = $filters['check_in_date'] ?? $filters['start_date'] ?? null;
        if ($checkInDate) {
            $query->whereDate('check_in_date', '>=', $checkInDate);
        }

        $checkOutDate = $filters['check_out_date'] ?? $filters['end_date'] ?? null;
        if ($checkOutDate) {
            $query->whereDate('check_out_date', '<=', $checkOutDate);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new reservation with tenant isolation and room overlap validation.
     */
    public function createReservation(array $data, ?string $hotelId = null, ?string $userId = null): Reservation
    {
        $hotelId = $hotelId ?: TenantContext::id();

        if (!$hotelId) {
            throw ValidationException::withMessages([
                'hotel_id' => ['Active hotel tenant context is required.'],
            ]);
        }

        return DB::transaction(function () use ($data, $hotelId, $userId) {
            // 1. Verify Room exists in this hotel
            $room = Room::where('hotel_id', $hotelId)
                ->with('roomType')
                ->findOrFail($data['room_id']);

            if (!$room->is_active || $room->status === 'maintenance') {
                throw ValidationException::withMessages([
                    'room_id' => ['Selected room is currently unavailable or under maintenance.'],
                ]);
            }

            // 2. Validate Room Capacity
            $capacity = $room->roomType?->capacity ?? 2;
            $numberOfGuests = (int) ($data['number_of_guests'] ?? $data['num_guests'] ?? 1);
            if ($numberOfGuests > $capacity) {
                throw ValidationException::withMessages([
                    'number_of_guests' => ["Room capacity exceeded (maximum {$capacity} guests)."],
                ]);
            }

            $checkIn = $data['check_in_date'];
            $checkOut = $data['check_out_date'];

            // 3. Prevent Overlapping Reservations for the same room
            if ($this->hasOverlapConflict($room->id, $checkIn, $checkOut, $hotelId)) {
                throw ValidationException::withMessages([
                    'room_id' => ['The selected room is already reserved for these dates.'],
                ]);
            }

            // 4. Resolve or Create Guest within Hotel Scope
            $guestId = $data['guest_id'] ?? null;
            if ($guestId) {
                $guest = Guest::where('hotel_id', $hotelId)->findOrFail($guestId);
            } else {
                $firstName = $data['first_name'] ?? $data['guest_info']['first_name'] ?? 'Guest';
                $lastName  = $data['last_name'] ?? $data['guest_info']['last_name'] ?? 'Booking';
                $email     = !empty($data['email']) ? $data['email'] : ($data['guest_info']['email'] ?? null);
                $phone     = !empty($data['phone']) ? $data['phone'] : ($data['guest_info']['phone'] ?? 'N/A');

                $guest = app(GuestService::class)->findOrCreateGuest([
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'email'      => !empty($email) ? strtolower(trim($email)) : null,
                    'phone'      => trim($phone),
                ], $hotelId);
                $guestId = $guest->id;
            }

            // 5. Calculate Total Amount
            $nights = max(1, (int) (new Carbon($checkIn))->diffInDays(new Carbon($checkOut)));
            $rate = (float) ($room->roomType?->base_price_per_night ?? $room->roomType?->price ?? 0);
            $totalAmount = $data['total_amount'] ?? ($nights * $rate);

            // 6. Create Reservation
            $reservation = Reservation::create([
                'hotel_id'          => $hotelId,
                'guest_id'          => $guestId,
                'room_id'           => $room->id,
                'check_in_date'     => $checkIn,
                'check_out_date'    => $checkOut,
                'number_of_guests'  => $numberOfGuests,
                'total_amount'      => $totalAmount,
                'status'            => $data['status'] ?? 'confirmed',
                'special_requests'  => $data['special_requests'] ?? null,
                'created_by'        => $userId,
            ]);

            $reservation->load(['guest', 'room.roomType', 'creator']);

            // Send confirmation email if email exists
            $this->sendConfirmationMail($reservation);

            return $reservation;
        });
    }

    /**
     * Update an existing reservation.
     */
    public function updateReservation(Reservation $reservation, array $data, ?string $hotelId = null): Reservation
    {
        $hotelId = $hotelId ?: $reservation->hotel_id ?: TenantContext::id();

        return DB::transaction(function () use ($reservation, $data, $hotelId) {
            $roomId = $data['room_id'] ?? $reservation->room_id;
            $checkIn = $data['check_in_date'] ?? $reservation->check_in_date;
            $checkOut = $data['check_out_date'] ?? $reservation->check_out_date;

            $room = Room::where('hotel_id', $hotelId)->with('roomType')->findOrFail($roomId);

            if (!empty($data['number_of_guests']) && $room->roomType) {
                if ((int) $data['number_of_guests'] > $room->roomType->capacity) {
                    throw ValidationException::withMessages([
                        'number_of_guests' => ["Room capacity exceeded (maximum {$room->roomType->capacity} guests)."],
                    ]);
                }
            }

            $newStatus = $data['status'] ?? $reservation->status;
            if ($newStatus !== 'cancelled') {
                if ($this->hasOverlapConflict($roomId, $checkIn, $checkOut, $hotelId, $reservation->id)) {
                    throw ValidationException::withMessages([
                        'room_id' => ['The selected room is already booked for these dates.'],
                    ]);
                }
            }

            $reservation->update($data);

            return $reservation->fresh(['guest', 'room.roomType', 'creator']);
        });
    }

    /**
     * Confirm a pending reservation.
     */
    public function confirmReservation(Reservation $reservation): Reservation
    {
        if ($reservation->status !== 'pending') {
            throw new \InvalidArgumentException('Only pending reservations can be confirmed.');
        }

        $reservation->update(['status' => 'confirmed']);
        $this->sendConfirmationMail($reservation);

        return $reservation->fresh(['guest', 'room.roomType', 'creator']);
    }

    /**
     * Cancel a reservation.
     */
    public function cancelReservation(Reservation $reservation, ?string $reason = null): Reservation
    {
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            throw new \InvalidArgumentException('Only pending or confirmed reservations can be cancelled.');
        }

        DB::transaction(function () use ($reservation, $reason) {
            $updateData = [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ];

            if ($reason && \Illuminate\Support\Facades\Schema::hasColumn('reservations', 'cancellation_reason')) {
                $updateData['cancellation_reason'] = $reason;
            }

            $reservation->update($updateData);

            // If room was blocked or occupied, restore availability
            if ($reservation->room && in_array($reservation->room->status, ['occupied', 'reserved'])) {
                $reservation->room->update(['status' => 'available']);
            }
        });

        return $reservation->fresh(['guest', 'room.roomType', 'creator']);
    }

    /**
     * Delete a reservation safely with related cleanup in a transaction.
     */
    public function deleteReservation(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            if ($reservation->checkIn) {
                $reservation->checkIn()->delete();
            }

            DB::table('orders')->where('reservation_id', $reservation->id)->update(['reservation_id' => null]);
            DB::table('payments')->where('reservation_id', $reservation->id)->update(['reservation_id' => null]);

            if ($reservation->room && $reservation->room->status === 'occupied') {
                $reservation->room->update(['status' => 'available']);
            }

            $reservation->delete();
        });
    }

    /**
     * Check if a room has overlapping active reservations.
     */
    public function hasOverlapConflict(string $roomId, $checkIn, $checkOut, ?string $hotelId = null, ?string $excludeReservationId = null): bool
    {
        $hotelId = $hotelId ?: TenantContext::id();

        $query = Reservation::where('room_id', $roomId)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in_date', '<', $checkOut)
                  ->where('check_out_date', '>', $checkIn);
            });

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }

    /**
     * Check room availability for a given date range.
     */
    public function checkAvailability(array $criteria, ?string $hotelId = null): array
    {
        $hotelId = $hotelId ?: TenantContext::id();
        $checkIn = $criteria['check_in_date'];
        $checkOut = $criteria['check_out_date'];

        $bookedRoomIds = Reservation::where('hotel_id', $hotelId)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in_date', '<', $checkOut)
                  ->where('check_out_date', '>', $checkIn);
            })
            ->pluck('room_id')
            ->toArray();

        // Check specific room
        if (!empty($criteria['room_id'])) {
            $room = Room::where('hotel_id', $hotelId)
                ->where('id', $criteria['room_id'])
                ->with('roomType')
                ->first();

            if (!$room) {
                return [
                    'available' => false,
                    'message' => 'Room not found in this hotel.',
                ];
            }

            $isAvailable = !in_array($room->id, $bookedRoomIds)
                && $room->is_active
                && $room->status !== 'maintenance';

            return [
                'hotel_id'       => $hotelId,
                'room_id'        => $room->id,
                'room_number'    => $room->room_number,
                'available'      => $isAvailable,
                'check_in_date'  => $checkIn,
                'check_out_date' => $checkOut,
                'message'        => $isAvailable ? 'Room is available.' : 'Room is not available for the selected dates.',
            ];
        }

        // Search all available rooms
        $query = Room::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->where('status', '!=', 'maintenance')
            ->whereNotIn('id', $bookedRoomIds)
            ->with('roomType');

        if (!empty($criteria['room_type_id'])) {
            $query->where('room_type_id', $criteria['room_type_id']);
        }

        if (!empty($criteria['capacity'])) {
            $cap = (int) $criteria['capacity'];
            $query->whereHas('roomType', fn($q) => $q->where('capacity', '>=', $cap));
        }

        $availableRooms = $query->get();

        return [
            'hotel_id'              => $hotelId,
            'check_in_date'         => $checkIn,
            'check_out_date'        => $checkOut,
            'available_rooms_count' => $availableRooms->count(),
            'rooms'                 => $availableRooms,
        ];
    }

    /**
     * Dispatch reservation confirmation email if guest email is available.
     */
    protected function sendConfirmationMail(Reservation $reservation): void
    {
        try {
            $email = $reservation->guest?->email;
            if ($email) {
                Mail::to($email)->send(new ReservationConfirmed($reservation));
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to send reservation confirmation email for #{$reservation->id}: {$e->getMessage()}");
        }
    }
}
