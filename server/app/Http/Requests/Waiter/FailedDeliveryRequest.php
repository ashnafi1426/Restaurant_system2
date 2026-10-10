<?php

namespace App\Http\Requests\Waiter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FailedDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'waiter';
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                Rule::in([
                    'guest_unavailable',
                    'wrong_room',
                    'guest_refused',
                    'order_damaged',
                    'order_incomplete',
                    'guest_requested_cancel',
                    'other',
                ]),
            ],
            'remarks' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Failure reason is required',
            'reason.in' => 'Selected reason is invalid',
            'remarks.max' => 'Remarks must not exceed 500 characters',
        ];
    }
}

