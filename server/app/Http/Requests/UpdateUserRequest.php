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
    protected function prepareForValidation(): void
    {
        if ($this->has('password') && ($this->password === '' || $this->password === null)) {
            $this->getInputSource()->remove('password');
            $this->getInputSource()->remove('password_confirmation');
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }
        if ($this->has('phone') && ($this->phone === '' || $this->phone === null)) {
            $this->merge(['phone' => null]);
        }
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
