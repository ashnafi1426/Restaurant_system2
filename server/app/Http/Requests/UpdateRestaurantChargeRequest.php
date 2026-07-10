<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantChargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Billing
            |--------------------------------------------------------------------------
            */

            'description' => [

                'sometimes',

                'string',

                'max:255',

            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => [

                'sometimes',

                'in:unpaid,paid,cancelled',

            ],

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'paid_at' => [

                'nullable',

                'date',

            ],

            'payment_reference' => [

                'nullable',

                'string',

                'max:100',

            ],

        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'status.in' => 'Invalid billing status.',

            'description.max' => 'Description may not exceed 255 characters.',

            'payment_reference.max' => 'Payment reference may not exceed 100 characters.',

        ];
    }

    /**
     * Friendly attribute names.
     */
    public function attributes(): array
    {
        return [

            'paid_at' => 'payment date',

            'payment_reference' => 'payment reference',

        ];
    }
}