<?php

namespace App\Http\Requests;

use App\Models\RestaurantTable;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelId = TenantContext::id() ?: $this->header('X-Hotel-ID') ?: $this->hotel_id;

        return [
            'table_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('restaurant_tables', 'table_number')
                    ->where(fn($q) => $hotelId ? $q->where('hotel_id', $hotelId) : $q)
                    ->whereNull('deleted_at'),
            ],
            'table_name' => 'nullable|string|max:255',
            'capacity'   => 'required|integer|min:1|max:30',
            'section_id' => 'nullable|uuid|exists:restaurant_sections,id',
            'section'    => 'nullable|string|max:100',
            'location'   => 'nullable|string|max:255',
            'status'     => [
                'nullable',
                Rule::in([
                    RestaurantTable::STATUS_AVAILABLE,
                    RestaurantTable::STATUS_OCCUPIED,
                    RestaurantTable::STATUS_RESERVED,
                    RestaurantTable::STATUS_CLEANING,
                    RestaurantTable::STATUS_OUT_OF_SERVICE,
                ]),
            ],
            'is_active'  => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'table_number.required' => 'Table number is required.',
            'table_number.unique'   => 'Table number already exists in this hotel.',
            'capacity.min'          => 'Table capacity must be at least 1 seat.',
            'capacity.max'          => 'Table capacity cannot exceed 30 seats.',
        ];
    }
}
