<?php

/*
|--------------------------------------------------------------------------
| Namespace
|--------------------------------------------------------------------------
|
| This Request class belongs to the Http\Requests namespace.
|
*/

namespace App\Http\Requests;

/*
|--------------------------------------------------------------------------
| Imports
|--------------------------------------------------------------------------
*/

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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

                Rule::unique('users')
                    ->ignore($this->route('user'))

            ],
            'phone' => [

                'nullable',

                'string',

                'max:20'

            ],
            'password' => [

                'nullable',

                'string',

                'min:8',

                'confirmed'

            ],

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
    public function messages(): array
    {
        return [

            'first_name.required'
            => 'First name is required.',

            'last_name.required'
            => 'Last name is required.',

            'email.required'
            => 'Email is required.',

            'email.unique'
            => 'Email already exists.',

            'password.confirmed'
            => 'Password confirmation does not match.',

            'role.required'
            => 'Role is required.',

            'role.in'
            => 'Selected role is invalid.',

        ];
    }

    /**
     * ------------------------------------------------------------------
     * Custom attribute names.
     * ------------------------------------------------------------------
     */

    public function attributes(): array
    {
        return [

            'first_name'
            => 'First Name',

            'last_name'
            => 'Last Name',

            'is_active'
            => 'Status',

        ];
    }
}
