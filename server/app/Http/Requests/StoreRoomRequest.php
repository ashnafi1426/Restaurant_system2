<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelId = \App\Services\TenantContext::id();

        $uniqueRoom = Rule::unique('rooms', 'room_number');
        $existsRoomType = Rule::exists('room_types', 'id');

        if ($hotelId) {
            $uniqueRoom = $uniqueRoom->where('hotel_id', $hotelId);
            $existsRoomType = $existsRoomType->where('hotel_id', $hotelId);
        }

        return [
            'room_number' => ['required', 'string', 'max:50', $uniqueRoom],
            'room_type_id' => ['required', $existsRoomType],
            'floor' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'occupied',
                    'cleaning',
                    'maintenance'
                ])
            ],

            'is_active' => ['required', 'boolean'],
        ];
    }
}