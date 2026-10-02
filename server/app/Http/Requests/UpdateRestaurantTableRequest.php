<?php

namespace App\Http\Requests;

use App\Models\RestaurantTable;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelId = TenantContext::id() ?: $this->header('X-Hotel-ID') ?: $this->hotel_id;
        $tableId = $this->route('id') ?: $this->route('restaurant_table') ?: $this->route('table');

        return [
            'table_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('restaurant_tables', 'table_number')
                    ->where(fn($q) => $hotelId ? $q->where('hotel_id', $hotelId) : $q)
                    ->whereNull('deleted_at')
                    ->ignore($tableId),
            ],
            'table_name' => 'nullable|string|max:255',
            'capacity'   => 'sometimes|required|integer|min:1|max:30',
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
}
