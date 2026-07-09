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
                \Log::info('📋 Validation check', [
                    'has_image' => $this->hasFile('image'),
                    'image_value' => $this->file('image') ? 'File object' : 'null',
                    'image_url_filled' => $this->filled('image_url'),
                    'all_keys' => array_keys($this->all()),
                    'all_input' => $this->all(),
                ]);

                if (!$this->hasFile('image') && !$this->filled('image_url')) {
                    \Log::error('❌ Validation failed: No image provided');
                    $validator->errors()->add(
                        'image',
                        'Either upload an image file or provide an image URL.'
                    );
                }
            },
        ];
    }
}