<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class ReassignDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }

    public function rules(): array
    {
        return [
            'waiter_id' => [
                'required',
                'exists:waiters,id',
                'different:current_waiter_id'
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500'
            ],
            'current_waiter_id' => [
                'required',
                'exists:waiters,id'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'waiter_id.required' => 'New waiter is required',
            'waiter_id.exists' => 'Selected waiter does not exist',
            'waiter_id.different' => 'New waiter must be different from current waiter',
            'current_waiter_id.required' => 'Current waiter information is required',
            'current_waiter_id.exists' => 'Current waiter does not exist',
            'reason.max' => 'Reason cannot exceed 500 characters',
        ];
    }

    public function getReassignmentData(): array
    {
        return $this->only([
            'waiter_id',
            'current_waiter_id',
            'reason',
        ]);
    }
}

