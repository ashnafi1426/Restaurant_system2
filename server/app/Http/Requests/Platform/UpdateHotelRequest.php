<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHotelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hotelId = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('hotels', 'slug')->ignore($hotelId)],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'This hotel slug is already taken.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }
}

