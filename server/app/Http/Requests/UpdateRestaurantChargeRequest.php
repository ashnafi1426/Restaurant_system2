<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'in:unpaid,paid,cancelled',
            ],
            'paid_at' => [
                'nullable',
                'date',
            ],
            'payment_reference' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid billing status.',
            'description.max' => 'Description may not exceed 255 characters.',
            'payment_reference.max' => 'Payment reference may not exceed 100 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'paid_at' => 'payment date',
            'payment_reference' => 'payment reference',
        ];
    }
}