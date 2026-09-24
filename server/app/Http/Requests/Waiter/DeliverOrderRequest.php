<?php

namespace App\Http\Requests\Waiter;

use Illuminate\Foundation\Http\FormRequest;

class DeliverOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'waiter';
    }

    public function rules(): array
    {
        return [
            'remarks' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.max' => 'Remarks must not exceed 500 characters',
        ];
    }
}
