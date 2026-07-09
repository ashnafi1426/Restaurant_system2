<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        $guestId = $this->route('guest');

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
                'nullable',
                'email',
                'max:255',
                Rule::unique('guests', 'email')->ignore($guestId)
            ],

            'phone' => [
                'required',
                'string',
                'max:20'
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000'
            ],

            'nationality' => [
                'nullable',
                'string',
                'max:100'
            ],

            'passport_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('guests', 'passport_number')->ignore($guestId)
            ],

            'date_of_birth' => [
                'nullable',
                'date',
                'before:today'
            ],

            'preferences' => [
                'nullable',
                'array'
            ],

            'preferences.*' => [
                'string',
                'max:255'
            ],

        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [

            'first_name.required' => 'First name is required.',

            'last_name.required' => 'Last name is required.',

            'email.email' => 'Please enter a valid email address.',

            'email.unique' => 'This email already exists.',

            'phone.required' => 'Phone number is required.',

            'passport_number.unique' => 'Passport number already exists.',

            'date_of_birth.before' => 'Date of birth must be before today.',

            'preferences.array' => 'Preferences must be an array.',

        ];
    }

    /**
     * Customize attribute names.
     */
    public function attributes(): array
    {
        return [

            'first_name' => 'First Name',

            'last_name' => 'Last Name',

            'date_of_birth' => 'Date of Birth',

            'passport_number' => 'Passport Number',

        ];
    }
}