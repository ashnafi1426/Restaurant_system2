<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    /**
     * Authorize the request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [

            'guest_id' => [
                'required',
                'uuid',
                'exists:guests,id',
            ],

            'room_id' => [
                'required',
                'uuid',
                'exists:rooms,id',
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

    /**
     * Custom validation messages.
     */
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