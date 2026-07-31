<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RegisterWaiterRequest
 * 
 * Validates waiter registration form data from manager
 * Ensures email uniqueness and password confirmation
 */
class RegisterWaiterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->where('role', 'waiter'),
            ],
            'phone' => ['required', 'string', 'regex:/^[0-9\+\-\s]{7,20}$/', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
            'employment_type' => [
                'required',
                Rule::in(['full_time', 'part_time', 'contract']),
            ],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'maximum_orders' => ['required', 'integer', 'min:1', 'max:20'],
            'profile_photo' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,gif'],
            'employee_number' => ['nullable', 'string', 'unique:waiters,employee_number'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.unique' => 'A waiter with this email already exists',
            'phone.regex' => 'Phone number format is invalid',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Passwords do not match',
            'employment_type.in' => 'Employment type must be full_time, part_time, or contract',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future',
            'maximum_orders.min' => 'Maximum orders must be at least 1',
            'maximum_orders.max' => 'Maximum orders cannot exceed 20',
            'profile_photo.image' => 'Profile photo must be an image',
            'profile_photo.max' => 'Profile photo must not exceed 2MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'employment_type' => strtolower($this->employment_type ?? ''),
        ]);
    }

    /**
     * Get the data for creating waiter
     */
    public function getWaiterData(): array
    {
        return $this->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'password',
            'employment_type',
            'hire_date',
            'maximum_orders',
            'profile_photo',
            'employee_number',
        ]);
    }
}
