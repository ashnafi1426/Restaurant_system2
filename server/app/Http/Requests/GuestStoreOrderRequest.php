<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuestStoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'qr_token' => [
                'required',
                'string',
                'max:255',
                Rule::exists('rooms', 'qr_token'),
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.menu_item_id' => [
                'required',
                'uuid',
                Rule::exists('menu_items', 'id'),
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Item Notes
            |--------------------------------------------------------------------------
            */

            'items.*.notes' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Order Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'qr_token.required' => 'QR token is required.',

            'qr_token.exists' => 'Invalid QR code.',

            'items.required' => 'Please select at least one menu item.',

            'items.array' => 'Items must be an array.',

            'items.min' => 'At least one menu item is required.',

            'items.*.menu_item_id.required' => 'Menu item is required.',

            'items.*.menu_item_id.exists' => 'Selected menu item does not exist.',

            'items.*.quantity.required' => 'Quantity is required.',

            'items.*.quantity.integer' => 'Quantity must be an integer.',

            'items.*.quantity.min' => 'Quantity must be at least 1.',

            'items.*.quantity.max' => 'Quantity cannot exceed 100.',

        ];
    }

    /**
     * Friendly attribute names.
     */
    public function attributes(): array
    {
        return [

            'qr_token' => 'QR code',

            'items' => 'order items',

            'items.*.menu_item_id' => 'menu item',

            'items.*.quantity' => 'quantity',

            'items.*.notes' => 'item note',

            'notes' => 'order note',

        ];
    }
}