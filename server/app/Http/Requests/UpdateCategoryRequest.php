<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = is_object($category) ? $category->id : $category;

        $hotelId = app(TenantContext::class)->getHotelId()
            ?? auth()->user()?->hotel_id
            ?? auth()->user()?->hotelMemberships()->first()?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')
                    ->where('hotel_id', $hotelId)
                    ->ignore($categoryId),
            ],
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'A category with this name already exists in your hotel.',
            'name.max' => 'Category name cannot exceed 100 characters.',
            'icon.max' => 'Icon cannot exceed 50 characters.',
        ];
    }
}
