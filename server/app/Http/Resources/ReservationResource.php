<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $guestFullName = $this->guest 
            ? trim(($this->guest->first_name ?? '') . ' ' . ($this->guest->last_name ?? '')) 
            : null;

        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'guest' => [
                'id' => $this->guest?->id,
                'name' => $guestFullName ?: 'N/A',
                'first_name' => $this->guest?->first_name,
                'last_name' => $this->guest?->last_name,
                'email' => $this->guest?->email,
                'phone' => $this->guest?->phone,
                'nationality' => $this->guest?->nationality,
            ],
            'room' => [
                'id' => $this->room?->id,
                'room_number' => $this->room?->room_number,
                'room_type' => $this->room?->roomType?->name
                    ?? $this->room?->type
                    ?? 'N/A',
                'floor' => $this->room?->floor,
                'status' => $this->room?->status,
            ],
            'check_in_date' => optional($this->check_in_date)->format('Y-m-d'),
            'check_out_date' => optional($this->check_out_date)->format('Y-m-d'),
            'number_of_guests' => (int) $this->number_of_guests,
            'total_nights' => (int) $this->total_nights,
            'stay_duration' => $this->stay_duration,
            'total_amount' => (float) $this->total_amount,
            'total_price' => (float) $this->total_amount,
            'payment_status' => $this->payment_status ?? 'pending',
            'hotel_id' => $this->hotel_id,
            'status' => $this->status,
            'special_requests' => $this->special_requests,
            'can_check_in' => $this->canCheckIn(),
            'can_check_out' => $this->canCheckOut(),
            'can_cancel' => $this->canCancel(),
            'is_active' => (bool) $this->is_active,
            'created_by' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
            'check_in' => $this->whenLoaded('checkIn', fn() => [
                'id' => $this->checkIn?->id,
                'checked_in_at' => optional($this->checkIn?->checked_in_at)->toDateTimeString(),
                'checked_out_at' => optional($this->checkIn?->checked_out_at)->toDateTimeString(),
            ]),
            'cancelled_at' => optional($this->cancelled_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}