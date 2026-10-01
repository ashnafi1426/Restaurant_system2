<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelId = \App\Services\TenantContext::id()
            ?: $this->input('hotel_id')
            ?: $this->header('X-Hotel-ID')
            ?: $this->header('x-hotel-id')
            ?: $this->user()?->hotel_id
            ?: $this->user()?->hotelMemberships()->where('is_active', true)->value('hotel_id');

        $uniqueName = Rule::unique('room_types', 'name');
        if ($hotelId) {
            $uniqueName = $uniqueName->where('hotel_id', $hotelId);
        }

        return [
            'hotel_id' => ['nullable', 'string'],
            'name' => [
                'required',
                'string',
                'max:100',
                $uniqueName,
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'base_price_per_night' => [
                'required',
                'numeric',
                'min:0',
            ],
            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],
            'amenities' => [
                'nullable',
                'array',
            ],
            'amenities.*' => [
                'string',
                'max:100',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Room type name is required.',
            'name.unique' => 'This room type already exists.',
            'base_price_per_night.required' => 'Price is required.',
            'capacity.required' => 'Capacity is required.',
            'capacity.min' => 'Capacity must be at least 1.',
        ];
    }
}