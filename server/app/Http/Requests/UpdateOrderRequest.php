<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
            | Order Information
            |--------------------------------------------------------------------------
            */

            'reservation_id' => [
                'required',
                'uuid',
                Rule::exists('reservations', 'id'),
            ],

            'guest_id' => [
                'required',
                'uuid',
                Rule::exists('guests', 'id'),
            ],

            'room_id' => [
                'required',
                'uuid',
                Rule::exists('rooms', 'id'),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Existing Order Item ID
            |--------------------------------------------------------------------------
            */

            'items.*.id' => [
                'nullable',
                'uuid',
                Rule::exists('order_items', 'id'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Menu Item
            |--------------------------------------------------------------------------
            */

            'items.*.menu_item_id' => [
                'required',
                'uuid',
                Rule::exists('menu_items', 'id'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

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

        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'reservation_id.required' => 'Reservation is required.',
            'reservation_id.exists' => 'The selected reservation does not exist.',

            'guest_id.required' => 'Guest is required.',
            'guest_id.exists' => 'The selected guest does not exist.',

            'room_id.required' => 'Room is required.',
            'room_id.exists' => 'The selected room does not exist.',

            'items.required' => 'At least one order item is required.',
            'items.array' => 'Items must be an array.',
            'items.min' => 'The order must contain at least one item.',

            'items.*.id.exists' => 'The selected order item does not exist.',

            'items.*.menu_item_id.required' => 'Menu item is required.',
            'items.*.menu_item_id.exists' => 'The selected menu item does not exist.',

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

            'reservation_id' => 'reservation',

            'guest_id' => 'guest',

            'room_id' => 'room',

            'items' => 'order items',

            'items.*.id' => 'order item',

            'items.*.menu_item_id' => 'menu item',

            'items.*.quantity' => 'quantity',

            'items.*.notes' => 'item note',

        ];
    }
}