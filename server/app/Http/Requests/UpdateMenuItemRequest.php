<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'category' => [
                'required',
                'string',
                'exists:categories,slug,is_active,1',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'image_url' => [
                'nullable',
                'url',
            ],
            'is_available' => [
                'required',
                'boolean',
            ],
            'tax_rate_id' => [
                'nullable',
                'uuid',
                'exists:tax_rates,id',
            ],
            'tax_included' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Menu item name is required.',
            'category.required' => 'Please select a category.',
            'category.exists' => 'The selected category is invalid or inactive.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be numeric.',
            'price.min' => 'Price must be greater than or equal to zero.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',
            'image.max' => 'The image cannot exceed 2MB.',
            'image_url.url' => 'Please provide a valid image URL.',
            'is_available.required' => 'Availability status is required.',
            'is_available.boolean' => 'Availability must be true or false.',
        ];
    }
}