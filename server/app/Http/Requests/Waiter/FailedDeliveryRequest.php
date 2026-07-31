<?php

namespace App\Http\Requests\Waiter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FailedDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'waiter';
    }

    /**
     * Get the validation rules that apply to the request.
     */
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

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Failure reason is required',
            'reason.in' => 'Selected reason is invalid',
            'remarks.max' => 'Remarks must not exceed 500 characters',
        ];
    }
}
