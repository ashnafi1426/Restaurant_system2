<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckInRequest extends FormRequest
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

            'checked_in_at' => [
                'nullable',
                'date',
            ],

            'expected_check_out_at' => [
                'nullable',
                'date',
                'after_or_equal:checked_in_at',
            ],

            'checked_out_at' => [
                'nullable',
                'date',
                'after_or_equal:checked_in_at',
            ],

        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'checked_in_at.date' =>
                'The check in date is invalid.',

            'expected_check_out_at.date' =>
                'The expected check out date is invalid.',

            'expected_check_out_at.after_or_equal' =>
                'Expected check out must be after the check in date.',

            'checked_out_at.date' =>
                'The check out date is invalid.',

            'checked_out_at.after_or_equal' =>
                'The check out date must be after the check in date.',

        ];
    }
}