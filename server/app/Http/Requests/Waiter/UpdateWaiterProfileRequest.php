<?php

namespace App\Http\Requests\Waiter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWaiterProfileRequest extends FormRequest
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
        $waiterId = auth()->id();

        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'shift' => 'sometimes|nullable|in:morning,afternoon,evening,night,flexible',
            'bio' => 'sometimes|nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.max' => 'First name must not exceed 255 characters',
            'last_name.max' => 'Last name must not exceed 255 characters',
            'phone.max' => 'Phone must not exceed 20 characters',
            'shift.in' => 'Selected shift is invalid',
            'bio.max' => 'Bio must not exceed 500 characters',
        ];
    }
}
