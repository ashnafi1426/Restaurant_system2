<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomTypeRequest extends FormRequest
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

                'unique:room_types,name',

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

    /**
     * -------------------------------------------------------------
     * Custom Validation Messages
     * -------------------------------------------------------------
     */

    public function messages(): array
    {
        return [

            'name.required' => 'Room type name is required.',

            'name.unique' => 'This room type already exists.',

            'base_price_per_night.required' => 'Price is required.',

            'capacity.required' => 'Capacity is required.',

            'capacity.min' => 'Capacity must be at least 1.',

        ];
    }
}