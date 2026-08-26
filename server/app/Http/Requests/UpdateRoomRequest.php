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

    protected function prepareForValidation(): void
    {
        if ($this->has('floor') && ($this->floor === '' || $this->floor === null)) {
            $this->merge(['floor' => null]);
        }
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
        }
    }

    public function rules(): array
    {
        $roomParam = $this->route('room');
        $roomId = is_object($roomParam) ? $roomParam->id : $roomParam;

        return [
            'room_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_number')->ignore($roomId, 'id'),
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

            'is_active' => ['nullable', 'boolean'],
        ];
    }
}