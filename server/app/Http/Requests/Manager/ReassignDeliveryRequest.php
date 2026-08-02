<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ReassignDeliveryRequest
 * 
 * Validates manual delivery reassignment
 * Ensures new waiter exists and is valid
 */
class ReassignDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }

    /**
     * Get the validation rules that apply to the request.
     */
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

    /**
     * Get the error messages for the defined validation rules.
     */
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

    /**
     * Get reassignment data
     */
    public function getReassignmentData(): array
    {
        return $this->only([
            'waiter_id',
            'current_waiter_id',
            'reason',
        ]);
    }
}
