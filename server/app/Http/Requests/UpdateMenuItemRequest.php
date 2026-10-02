<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $hotelId = app(TenantContext::class)->getHotelId()
            ?? auth()->user()?->hotel_id
            ?? auth()->user()?->hotelMemberships()->first()?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'category' => [
                'required',
                'string',
                Rule::exists('categories', 'slug')
                    ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->where('is_active', true),
            ],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')
                    ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId)),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'image_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'is_available' => [
                'sometimes',
                'boolean',
            ],
            'tax_rate_id' => [
                'nullable',
                'uuid',
                Rule::exists('tax_rates', 'id')
                    ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->where('is_active', true),
            ],
            'tax_included' => [
                'sometimes',
                'boolean',
            ],
            'dietary_tags' => [
                'sometimes',
                'array',
            ],
            'dietary_tags.*' => [
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Menu item name is required.',
            'category.required' => 'Please select a category.',
            'category.exists' => 'The selected category does not exist in your hotel or is inactive.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price must be greater than zero.',
            'tax_rate_id.exists' => 'The selected tax rate does not belong to your hotel or is inactive.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',
            'image.max' => 'The image cannot exceed 5MB.',
            'image_url.url' => 'Please provide a valid image URL.',
        ];
    }
}