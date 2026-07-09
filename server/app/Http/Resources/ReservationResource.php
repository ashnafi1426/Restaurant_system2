<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'booking_reference' => $this->booking_reference,

            /*
            |--------------------------------------------------------------------------
            | Guest
            |--------------------------------------------------------------------------
            */

            'guest' => [

                'id' => $this->guest?->id,

                'full_name' => trim(
                    ($this->guest?->first_name ?? '') . ' ' .
                    ($this->guest?->last_name ?? '')
                ),

                'email' => $this->guest?->email,

                'phone' => $this->guest?->phone,

                'nationality' => $this->guest?->nationality,

            ],

            /*
            |--------------------------------------------------------------------------
            | Room
            |--------------------------------------------------------------------------
            */

            'room' => [

                'id' => $this->room?->id,

                'room_number' => $this->room?->room_number,

                'room_type' => $this->room?->roomType?->name
                    ?? $this->room?->type
                    ?? null,

                'floor' => $this->room?->floor,

                'status' => $this->room?->status,

            ],

            /*
            |--------------------------------------------------------------------------
            | Reservation
            |--------------------------------------------------------------------------
            */

            'check_in_date' => optional($this->check_in_date)
                ->format('Y-m-d'),

            'check_out_date' => optional($this->check_out_date)
                ->format('Y-m-d'),

            'number_of_guests' => $this->number_of_guests,

            'total_nights' => $this->total_nights,

            'stay_duration' => $this->stay_duration,

            'status' => $this->status,

            'special_requests' => $this->special_requests,

            /*
            |--------------------------------------------------------------------------
            | Status Flags
            |--------------------------------------------------------------------------
            */

            'can_check_in' => $this->canCheckIn(),

            'can_check_out' => $this->canCheckOut(),

            'can_cancel' => $this->canCancel(),

            'is_active' => $this->is_active,

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_by' => [

                'id' => $this->creator?->id,

                'name' => $this->creator?->name,

            ],

            'cancelled_at' => optional($this->cancelled_at)
                ->toDateTimeString(),

            'created_at' => optional($this->created_at)
                ->toDateTimeString(),

            'updated_at' => optional($this->updated_at)
                ->toDateTimeString(),

        ];
    }
}