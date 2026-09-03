<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('floor') && ($this->floor === '' || $this->floor === null)) {
            $this->merge(['floor' => null]);
        }
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
        }
    }

    public function rules(): array
    {
        $roomParam = $this->route('room');
        $roomId = is_object($roomParam) ? $roomParam->id : $roomParam;
        $hotelId = \App\Services\TenantContext::id();

        $uniqueRoom = Rule::unique('rooms', 'room_number')->ignore($roomId, 'id');
        $existsRoomType = Rule::exists('room_types', 'id');

        if ($hotelId) {
            $uniqueRoom = $uniqueRoom->where('hotel_id', $hotelId);
            $existsRoomType = $existsRoomType->where('hotel_id', $hotelId);
        }

        return [
            'room_number' => [
                'required',
                'string',
                'max:50',
                $uniqueRoom,
            ],

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

            'is_active' => ['nullable', 'boolean'],
        ];
    }
}