<?php

// Namespace of this Request class
namespace App\Http\Requests;

// Import FormRequest
use Illuminate\Foundation\Http\FormRequest;

// Import validation Rule class
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

            /*
            |--------------------------------------------------------------------------
            | First Name
            |--------------------------------------------------------------------------
            | Required
            | String only
            | Maximum 100 characters
            */
            'first_name' => [
                'required',
                'string',
                'max:100'
            ],

            /*
            |--------------------------------------------------------------------------
            | Last Name
            |--------------------------------------------------------------------------
            */
            'last_name' => [
                'required',
                'string',
                'max:100'
            ],

            /*
            |--------------------------------------------------------------------------
            | Email Address
            |--------------------------------------------------------------------------
            | Required
            | Must be email
            | Must be unique in users table
            */
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email'
            ],

            /*
            |--------------------------------------------------------------------------
            | Phone Number
            |--------------------------------------------------------------------------
            | Optional
            */
            'phone' => [
                'nullable',
                'string',
                'max:20'
            ],

            /*
            |--------------------------------------------------------------------------
            | Password
            |--------------------------------------------------------------------------
            | We receive "password"
            | Later controller stores it into password_hash
            */
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ],

            /*
            |--------------------------------------------------------------------------
            | User Role
            |--------------------------------------------------------------------------
            | Only allowed roles
            */
            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'receptionist',
                    'cashier',
                    'manager',
                    'chef'
                ])
            ],

            /*
            |--------------------------------------------------------------------------
            | User Status
            |--------------------------------------------------------------------------
            */
            'is_active' => [
                'required',
                'boolean'
            ]

        ];
    }

    /**
     * ---------------------------------------------------------
     * Custom validation messages.
     *
     * These replace Laravel's default messages.
     * ---------------------------------------------------------
     */
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

            'password.required' =>
            'Password is required.',

            'password.confirmed' =>
            'Password confirmation does not match.',

            'role.required' =>
            'Role is required.',

            'role.in' =>
            'Selected role is invalid.',

        ];
    }

    /**
     * ---------------------------------------------------------
     * Custom attribute names.
     *
     * These names appear in validation errors.
     * ---------------------------------------------------------
     */
    public function attributes(): array
    {
        return [

            'first_name' => 'First Name',

            'last_name' => 'Last Name',

            'is_active' => 'Status'

        ];
    }
}
