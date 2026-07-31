<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => 'required|string|min:10|max:1000',
            'satisfaction_rating' => 'nullable|integer|min:1|max:5',
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
            'resolution_notes.required' => 'Resolution notes are required',
            'resolution_notes.min' => 'Resolution notes must be at least 10 characters',
            'resolution_notes.max' => 'Resolution notes cannot exceed 1000 characters',
            'satisfaction_rating.integer' => 'Satisfaction rating must be a number',
            'satisfaction_rating.min' => 'Satisfaction rating must be at least 1',
            'satisfaction_rating.max' => 'Satisfaction rating cannot exceed 5',
        ];
    }
}
