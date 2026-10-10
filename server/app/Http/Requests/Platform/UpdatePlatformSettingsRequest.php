<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform_name' => 'nullable|string|max:255',
            'default_currency' => 'nullable|string|max:10',
            'default_timezone' => 'nullable|string|max:50',
            'maintenance_mode' => ['nullable', 'string', Rule::in(['true', 'false'])],
            'support_email' => 'nullable|email|max:255',
            'allow_hotel_registration' => ['nullable', 'string', Rule::in(['true', 'false'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'support_email.email' => 'Please provide a valid support email address.',
            'maintenance_mode.in' => 'Maintenance mode must be either true or false.',
            'allow_hotel_registration.in' => 'Hotel registration setting must be either true or false.',
        ];
    }
}

