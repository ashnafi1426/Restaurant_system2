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
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($waiterId),
            ],
            'phone' => 'sometimes|string|max:20',
            'shift' => 'sometimes|in:morning,afternoon,night,flexible',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Name must not exceed 255 characters',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email is already taken',
            'phone.max' => 'Phone must not exceed 20 characters',
            'shift.in' => 'Selected shift is invalid',
        ];
    }
}
