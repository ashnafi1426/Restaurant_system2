<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWaiterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }

    public function rules(): array
    {
        $waiterId = $this->route('waiter') ?? $this->route('id');

        return [
            'phone' => [
                'sometimes',
                'string',
                'regex:/^[0-9\+\-\s]{7,20}$/',
                'max:20'
            ],
            'employment_type' => [
                'sometimes',
                Rule::in(['full_time', 'part_time', 'contract']),
            ],
            'maximum_orders' => [
                'sometimes',
                'integer',
                'min:1',
                'max:20'
            ],
            'profile_photo' => [
                'nullable',
                'image',
                'max:2048',
                'mimes:jpeg,png,jpg,gif'
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'suspended']),
            ],
            'availability' => [
                'sometimes',
                Rule::in(['available', 'busy', 'break', 'offline']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number format is invalid',
            'employment_type.in' => 'Employment type must be full_time, part_time, or contract',
            'maximum_orders.min' => 'Maximum orders must be at least 1',
            'maximum_orders.max' => 'Maximum orders cannot exceed 20',
            'profile_photo.image' => 'Profile photo must be an image',
            'profile_photo.max' => 'Profile photo must not exceed 2MB',
            'status.in' => 'Status must be active, inactive, or suspended',
            'availability.in' => 'Availability must be available, busy, break, or offline',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('employment_type')) {
            $this->merge([
                'employment_type' => strtolower($this->employment_type),
            ]);
        }

        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower($this->status),
            ]);
        }

        if ($this->has('availability')) {
            $this->merge([
                'availability' => strtolower($this->availability),
            ]);
        }
    }

    public function getWaiterData(): array
    {
        return $this->only([
            'phone',
            'employment_type',
            'maximum_orders',
            'profile_photo',
            'status',
            'availability',
        ]);
    }
}

