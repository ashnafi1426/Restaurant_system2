<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
                'min:6',
                'max:255',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.menu_item_id' => [
                'required',
                'uuid',
                'exists:menu_items,id',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'items.*.notes' => [
                'nullable',
                'string',
                'max:255',
            ],
            'special_requests' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'payment_type' => [
                'nullable',
                'string',
                'in:room_charge,cash,card,chapa',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'qr_token.required' => 'QR code is required.',
            'items.required' => 'Please select at least one menu item.',
            'items.min' => 'At least one menu item is required.',
            'items.*.menu_item_id.required' => 'Menu item is required.',
            'items.*.menu_item_id.exists' => 'Selected menu item does not exist.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}