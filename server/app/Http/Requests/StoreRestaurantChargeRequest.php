<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantChargeRequest extends FormRequest
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
            | Relationships
            |--------------------------------------------------------------------------
            */

            'reservation_id' => [

                'required',

                'uuid',

                'exists:reservations,id',

            ],

            'order_id' => [

                'required',

                'uuid',

                'exists:orders,id',

                'unique:restaurant_charges,order_id',

            ],

            /*
            |--------------------------------------------------------------------------
            | Billing
            |--------------------------------------------------------------------------
            */

            'amount' => [

                'required',

                'numeric',

                'min:0',

            ],

            'description' => [

                'required',

                'string',

                'max:255',

            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => [

                'nullable',

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

            'reservation_id.required' => 'Reservation is required.',

            'reservation_id.exists' => 'Reservation not found.',

            'order_id.required' => 'Order is required.',

            'order_id.exists' => 'Order not found.',

            'order_id.unique' => 'This order has already been billed.',

            'amount.required' => 'Charge amount is required.',

            'amount.numeric' => 'Amount must be numeric.',

            'amount.min' => 'Amount cannot be negative.',

            'description.required' => 'Description is required.',

            'status.in' => 'Invalid billing status.',

        ];
    }

    /**
     * Friendly attribute names.
     */
    public function attributes(): array
    {
        return [

            'reservation_id' => 'reservation',

            'order_id' => 'restaurant order',

            'paid_at' => 'payment date',

            'payment_reference' => 'payment reference',

        ];
    }
}