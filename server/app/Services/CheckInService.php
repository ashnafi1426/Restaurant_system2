<?php

namespace App\Services;

use App\Mail\CheckInConfirmed;
use App\Mail\CheckOutNotification;
use App\Models\CheckIn;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CheckInService
{
    /**
     * Get paginated check-ins scoped to current hotel tenant.
     */
    public function getCheckIns(array $filters = [], int $perPage = 10, ?string $hotelId = null): LengthAwarePaginator
    {
        $hotelId = $hotelId ?: TenantContext::id();

        $query = CheckIn::select([
            'id', 'hotel_id', 'guest_id', 'room_id', 'reservation_id',
            'checked_in_at', 'checked_out_at', 'actual_checkout_date',
            'notes', 'created_at', 'updated_at'
        ])->with([
            'guest:id,first_name,last_name,email,phone,hotel_id',
            'room:id,room_number,room_type_id,status,hotel_id',
            'room.roomType:id,name,base_price_per_night,capacity',
            'reservation:id,booking_reference,check_in_date,check_out_date,status'
        ]);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', function ($g) use ($search) {
                    $g->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('room', function ($r) use ($search) {
                    $r->where('room_number', 'like', "%{$search}%");
                })
                ->orWhereHas('reservation', function ($res) use ($search) {
                    $res->where('booking_reference', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($filters['guest_id'])) {
            $query->where('guest_id', $filters['guest_id']);
        }

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['status'])) {
            $status = strtolower($filters['status']);
            if (in_array($status, ['active', 'checked_in'])) {
                $query->whereNull('checked_out_at');
            } elseif ($status === 'checked_out') {
                $query->whereNotNull('checked_out_at');
            }
        }

        return $query->latest('checked_in_at')->paginate($perPage);
    }

    /**
     * Perform guest check-in: update reservation status to checked_in and room to occupied.
     */
    public function checkIn(Reservation|string $reservationOrId, ?string $hotelId = null): CheckIn
    {
        $hotelId = $hotelId ?: TenantContext::id();

        $reservation = $reservationOrId instanceof Reservation
            ? $reservationOrId
            : Reservation::where('hotel_id', $hotelId)->with(['room', 'guest'])->findOrFail($reservationOrId);

        if (!$reservation->canCheckIn()) {
            throw ValidationException::withMessages([
                'reservation_id' => ["Only confirmed reservations can be checked in. Current status: {$reservation->status}."],
            ]);
        }

        if ($reservation->room && $reservation->room->status === 'maintenance') {
            throw ValidationException::withMessages([
                'room_id' => ['The assigned room is currently under maintenance.'],
            ]);
        }

        return DB::transaction(function () use ($reservation, $hotelId) {
            // 1. Update reservation status
            $reservation->update(['status' => 'checked_in']);

            // 2. Mark Room as Occupied (enables room-service ordering eligibility)
            if ($reservation->room) {
                $reservation->room->update(['status' => 'occupied']);
            }

            // 3. Create or Update CheckIn record
            $checkIn = CheckIn::firstOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'hotel_id'              => $reservation->hotel_id ?: $hotelId,
                    'guest_id'              => $reservation->guest_id,
                    'room_id'               => $reservation->room_id,
                    'checked_in_at'         => now(),
                    'expected_check_out_at' => $reservation->check_out_date,
                ]
            );

            // 4. Send Confirmation Email to Guest
            $this->sendCheckInEmail($reservation);

            return $checkIn->fresh(['guest', 'room.roomType', 'reservation']);
        });
    }

    /**
     * Perform guest checkout: update reservation to checked_out, room to available, and record timestamp.
     */
    public function checkOut(CheckIn|Reservation|string $target, ?string $hotelId = null): CheckIn
    {
        $hotelId = $hotelId ?: TenantContext::id();

        // Resolve CheckIn and Reservation models
        if ($target instanceof CheckIn) {
            $checkIn = $target;
            $reservation = $checkIn->reservation;
        } elseif ($target instanceof Reservation) {
            $reservation = $target;
            $checkIn = $reservation->checkIn;
        } else {
            // Try resolving by check_in ID or reservation ID
            $checkIn = CheckIn::where('id', $target)
                ->orWhere('reservation_id', $target)
                ->with(['reservation', 'room', 'guest'])
                ->first();

            if (!$checkIn) {
                $reservation = Reservation::where('id', $target)
                    ->orWhere('booking_reference', $target)
                    ->with(['room', 'guest'])
                    ->firstOrFail();
                $checkIn = $reservation->checkIn;
            } else {
                $reservation = $checkIn->reservation;
            }
        }

        if (!$reservation || $reservation->status !== 'checked_in') {
            throw ValidationException::withMessages([
                'reservation_id' => ['Only checked-in guests can be checked out.'],
            ]);
        }

        if ($checkIn && $checkIn->checked_out_at) {
            throw ValidationException::withMessages([
                'check_in_id' => ['Guest has already been checked out.'],
            ]);
        }

        return DB::transaction(function () use ($reservation, $checkIn, $hotelId) {
            $now = now();

            // 1. Update Reservation status to checked_out
            $reservation->update(['status' => 'checked_out']);

            // 2. Update Room status to available
            if ($reservation->room) {
                $reservation->room->update(['status' => 'available']);
            }

            // 3. Update or create CheckIn record
            if ($checkIn) {
                $checkIn->update(['checked_out_at' => $now]);
            } else {
                $checkIn = CheckIn::create([
                    'hotel_id'              => $reservation->hotel_id ?: $hotelId,
                    'reservation_id'        => $reservation->id,
                    'guest_id'              => $reservation->guest_id,
                    'room_id'               => $reservation->room_id,
                    'checked_in_at'         => $reservation->updated_at ?: $now,
                    'expected_check_out_at' => $reservation->check_out_date,
                    'checked_out_at'        => $now,
                ]);
            }

            // 4. Send Checkout Notification Email
            $this->sendCheckOutEmail($reservation);

            return $checkIn->fresh(['guest', 'room.roomType', 'reservation']);
        });
    }

    /**
     * Get check-in statistics scoped strictly to the current hotel.
     */
    public function getStatistics(?string $hotelId = null): array
    {
        $hotelId = $hotelId ?: TenantContext::id();

        $query = CheckIn::query();
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $today = today();

        $stats = (clone $query)->selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN DATE(checked_in_at) = ? THEN 1 ELSE 0 END) as today_count,
            SUM(CASE WHEN checked_out_at IS NULL THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN DATE(expected_check_out_at) = ? AND checked_out_at IS NULL THEN 1 ELSE 0 END) as expected_today_count
        ", [$today->toDateString(), $today->toDateString()])->first();

        return [
            'total_check_ins' => (int) ($stats->total_count ?? 0),
            'today_check_ins' => (int) ($stats->today_count ?? 0),
            'active_guests'   => (int) ($stats->active_count ?? 0),
            'expected_today'  => (int) ($stats->expected_today_count ?? 0),
        ];
    }

    /**
     * Delete check-in record and reset room status if still occupied.
     */
    public function deleteCheckIn(CheckIn $checkIn): void
    {
        DB::transaction(function () use ($checkIn) {
            if ($checkIn->room && $checkIn->room->status === 'occupied') {
                $checkIn->room->update(['status' => 'available']);
            }

            if ($checkIn->reservation && $checkIn->reservation->status === 'checked_in') {
                $checkIn->reservation->update(['status' => 'confirmed']);
            }

            $checkIn->delete();
        });
    }

    /**
     * Send check-in confirmation email to guest.
     */
    protected function sendCheckInEmail(Reservation $reservation): void
    {
        try {
            $email = $reservation->guest?->email;
            if ($email) {
                Mail::to($email)->send(new CheckInConfirmed($reservation));
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to send check-in email for reservation #{$reservation->id}: {$e->getMessage()}");
        }
    }

    /**
     * Send check-out notification email to guest.
     */
    protected function sendCheckOutEmail(Reservation $reservation): void
    {
        try {
            $email = $reservation->guest?->email;
            if ($email) {
                Mail::to($email)->send(new CheckOutNotification($reservation));
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to send checkout email for reservation #{$reservation->id}: {$e->getMessage()}");
        }
    }
}
