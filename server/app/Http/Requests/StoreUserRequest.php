<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100'
            ],
            'last_name' => [
                'required',
                'string',
                'max:100'
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20'
            ],
            'role' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    $slug = strtolower($value);
                    $exists = \App\Models\Role::whereRaw('LOWER(slug) = ?', [$slug])
                        ->orWhereRaw('LOWER(name) = ?', [$slug])
                        ->exists();
                    if (!$exists) {
                        $fail('The selected role is invalid.');
                    }
                }
            ],
            'is_active' => [
                'required',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' =>
            'First name is required.',
            'last_name.required' =>
            'Last name is required.',
            'email.required' =>
            'Email is required.',
            'email.unique' =>
            'Email already exists.',
            'role.required' =>
            'Role is required.',
            'role.in' =>
            'Selected role is invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'is_active' => 'Status'
        ];
    }
}
