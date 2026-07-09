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

            'reservation' => [
                'id' => $this->reservation?->id,
                'booking_reference' => $this->reservation?->booking_reference,
                'status' => $this->reservation?->status,
            ],

            'guest' => [
                'id' => $this->guest?->id,
                'full_name' => $this->guest?->full_name,
                'phone' => $this->guest?->phone,
                'email' => $this->guest?->email,
            ],

            'room' => [
                'id' => $this->room?->id,
                'room_number' => $this->room?->room_number,
                'status' => $this->room?->status,
            ],

            'created_at' => $this->created_at,
        ];
    }
}