<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $hotelId = TenantContext::id()
            ?? app(TenantContext::class)->getHotelId()
            ?? auth()->user()?->hotelMemberships()->first()?->id;

        return [
            'order_type' => [
                'nullable',
                'string',
                Rule::in([
                    Order::TYPE_ROOM_SERVICE,
                    Order::TYPE_DINE_IN,
                    Order::TYPE_WALK_IN,
                ]),
            ],
            'room_id' => [
                Rule::requiredIf(fn() => $this->input('order_type') === Order::TYPE_ROOM_SERVICE),
                'nullable',
                'uuid',
                Rule::exists('rooms', 'id')->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId)),
            ],
            'reservation_id' => [
                'nullable',
                'uuid',
                Rule::exists('reservations', 'id')->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId)),
            ],
            'guest_id' => [
                'nullable',
                'uuid',
                Rule::exists('guests', 'id'),
            ],
            'table_id' => [
                Rule::requiredIf(fn() => $this->input('order_type') === Order::TYPE_DINE_IN),
                'nullable',
                'uuid',
                Rule::exists('restaurant_tables', 'id')->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId)),
            ],
            'payment_type' => [
                'nullable',
                'string',
                Rule::in(['room_charge', 'cash', 'card', 'chapa', 'online']),
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.menu_item_id' => [
                'required',
                'uuid',
                Rule::exists('menu_items', 'id')->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId)),
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
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Room is required for room service orders.',
            'room_id.exists' => 'The selected room is invalid or belongs to another hotel.',
            'table_id.required' => 'Table is required for dine-in orders.',
            'table_id.exists' => 'The selected table is invalid or belongs to another hotel.',
            'items.required' => 'Please select at least one menu item.',
            'items.min' => 'At least one menu item is required.',
            'items.*.menu_item_id.required' => 'Menu item is required.',
            'items.*.menu_item_id.exists' => 'Selected menu item does not exist or belongs to another hotel.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}