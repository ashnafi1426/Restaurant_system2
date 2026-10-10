<?php

namespace App\Http\Requests\Manager;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $hotelId = app(TenantContext::class)->getHotelId();
        $floor = $this->route('floor');
        $floorId = is_object($floor) ? $floor->id : $floor;

        return [
            'floor_number' => [
                'sometimes',
                'integer',
                'min:-10',
                'max:200',
                Rule::unique('floors', 'floor_number')
                    ->where(fn($q) => $hotelId ? $q->where('hotel_id', $hotelId) : $q)
                    ->ignore($floorId),
            ],
            'name' => [
                'sometimes',
                'string',
                'min:1',
                'max:100',
                Rule::unique('floors', 'name')
                    ->where(fn($q) => $hotelId ? $q->where('hotel_id', $hotelId) : $q)
                    ->ignore($floorId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'total_rooms' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

