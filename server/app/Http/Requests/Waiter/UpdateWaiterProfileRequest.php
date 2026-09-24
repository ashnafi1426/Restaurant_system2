<?php

namespace App\Http\Requests\Waiter;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaiterProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'waiter';
    }

    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'shift' => 'sometimes|nullable|in:morning,afternoon,evening,night,flexible',
            'bio' => 'sometimes|nullable|string|max:500',
        ];
    }

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
