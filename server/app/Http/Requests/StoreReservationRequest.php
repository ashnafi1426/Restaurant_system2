<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelId = \App\Services\TenantContext::id();

        $roomRule = Rule::exists('rooms', 'id');
        $guestRule = Rule::exists('guests', 'id');

        if ($hotelId) {
            $roomRule = $roomRule->where('hotel_id', $hotelId)->where('is_active', true);
            $guestRule = $guestRule->where('hotel_id', $hotelId);
        }

        return [
            'guest_id' => [
                'nullable',
                'uuid',
                $guestRule,
            ],
            'first_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'room_id' => [
                'required',
                'uuid',
                $roomRule,
            ],
            'check_in_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'check_out_date' => [
                'required',
                'date',
                'after:check_in_date',
            ],
            'number_of_guests' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],
            'status' => [
                'sometimes',
                Rule::in([
                    'pending',
                    'confirmed',
                    'checked_in',
                    'checked_out',
                    'cancelled',
                ]),
            ],
            'special_requests' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.required' =>
                'Please select a guest.',
            'guest_id.exists' =>
                'Selected guest does not exist.',
            'room_id.required' =>
                'Please select a room.',
            'room_id.exists' =>
                'Selected room does not exist.',
            'check_in_date.after_or_equal' =>
                'Check-in date cannot be in the past.',
            'check_out_date.after' =>
                'Check-out must be after check-in.',
            'number_of_guests.min' =>
                'At least one guest is required.',
            'number_of_guests.max' =>
                'Guest count exceeds the allowed limit.',
        ];
    }
}

