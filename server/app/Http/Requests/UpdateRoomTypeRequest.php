<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomTypeRequest extends FormRequest
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
                'max:100',
                Rule::unique(
                    'room_types',
                    'name'
                )->ignore(
                    $this->route('room_type')
                ),
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

            'name.required' =>

                'Room type name is required.',

            'name.unique' =>

                'This room type name already exists.',

            'base_price_per_night.required' =>

                'Base price is required.',

            'base_price_per_night.numeric' =>

                'Base price must be numeric.',

            'capacity.required' =>

                'Capacity is required.',

            'capacity.integer' =>

                'Capacity must be an integer.',

            'capacity.min' =>

                'Capacity must be at least 1.',

            'amenities.array' =>

                'Amenities must be an array.',

            'is_active.boolean' =>

                'Status must be true or false.',

        ];
    }
}