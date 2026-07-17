<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
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
                'in:breakfast,lunch,dinner,drinks,dessert,appetizers',
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
                'sometimes',
                'boolean',
            ],

        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'name.required' => 'Menu item name is required.',

            'category.required' => 'Please select a category.',

            'category.in' => 'Invalid menu category.',

            'price.required' => 'Price is required.',

            'price.numeric' => 'Price must be a valid number.',

            'price.min' => 'Price cannot be negative.',

            'image.image' => 'The file must be an image.',

            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',

            'image.max' => 'The image cannot exceed 2MB.',

            'image_url.url' => 'Please provide a valid image URL.',

            'is_available.boolean' => 'Availability must be true or false.',

        ];
    }

    /**
     * Validate that at least one image source is provided.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                \Log::info('📋 Validation check in after() method', [
                    'has_file_image' => $this->hasFile('image'),
                    'filled_image_url' => $this->filled('image_url'),
                    'all_keys' => array_keys($this->all()),
                ]);

                $hasImage = $this->hasFile('image');
                $hasImageUrl = $this->filled('image_url');

                if (!$hasImage && !$hasImageUrl) {
                    \Log::error('❌ Validation failed: No image provided', [
                        'has_file_image' => $hasImage,
                        'filled_image_url' => $hasImageUrl,
                        'image_value' => $this->input('image'),
                        'image_url_value' => $this->input('image_url'),
                    ]);
                    
                    $validator->errors()->add(
                        'image',
                        'Either upload an image file or provide an image URL.'
                    );
                } else {
                    \Log::info('✅ Image validation passed', [
                        'has_image' => $hasImage,
                        'has_image_url' => $hasImageUrl,
                    ]);
                }
            },
        ];
    }
}