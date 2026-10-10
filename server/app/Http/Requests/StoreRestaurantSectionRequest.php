<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRestaurantSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelId = TenantContext::id() ?: $this->header('X-Hotel-ID') ?: $this->hotel_id;
        $sectionId = $this->route('id') ?: $this->route('section');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('restaurant_sections', 'name')
                    ->where(fn($q) => $hotelId ? $q->where('hotel_id', $hotelId) : $q)
                    ->ignore($sectionId),
            ],
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ];
    }
}

