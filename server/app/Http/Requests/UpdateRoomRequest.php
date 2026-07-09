<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomId = $this->route('room');

        return [
            'room_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_number')->ignore($roomId),
            ],

            'room_type_id' => ['required', 'exists:room_types,id'],
            'floor' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'occupied',
                    'cleaning',
                    'maintenance'
                ])
            ],

            'is_active' => ['required', 'boolean'],
        ];
    }
}