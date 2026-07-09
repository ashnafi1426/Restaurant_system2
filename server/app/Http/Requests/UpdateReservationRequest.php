<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
                'required',
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
}