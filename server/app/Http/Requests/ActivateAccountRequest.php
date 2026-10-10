<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ActivateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'size:36'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'password_confirmation' => [
                'required',
                'string'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Activation token is required.',
            'token.size' => 'Invalid activation token format.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Password confirmation is required.'
        ];
    }

    public function attributes(): array
    {
        return [
            'password_confirmation' => 'password confirmation'
        ];
    }
}

