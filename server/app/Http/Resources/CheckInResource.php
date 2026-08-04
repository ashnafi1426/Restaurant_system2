<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'guest_id' => $this->guest_id,
            'room_id' => $this->room_id,

            'checked_in_at' => $this->checked_in_at,

            'expected_check_out_at' => $this->expected_check_out_at,

            'checked_out_at' => $this->checked_out_at,

            'guest' => [
                'id' => $this->guest?->id,
                'first_name' => $this->guest?->first_name,
                'last_name' => $this->guest?->last_name,
                'full_name' => $this->guest?->full_name,
                'phone' => $this->guest?->phone,
                'email' => $this->guest?->email,
            ],

            'room' => [
                'id' => $this->room?->id,
                'room_number' => $this->room?->room_number,
                'status' => $this->room?->status,
                'room_type' => $this->room?->roomType ? [
                    'id' => $this->room->roomType->id,
                    'name' => $this->room->roomType->name,
                ] : null,
            ],

            'reservation' => [
                'id' => $this->reservation?->id,
                'reservation_number' => $this->reservation?->reservation_number ?? $this->reservation?->booking_reference,
                'booking_reference' => $this->reservation?->booking_reference,
                'status' => $this->reservation?->status,
                'check_in_date' => $this->reservation?->check_in_date,
                'check_out_date' => $this->reservation?->check_out_date,
            ],

            'created_at' => $this->created_at,
        ];
    }
}