<?php

namespace App\Http\Requests\Waiter;

use Illuminate\Foundation\Http\FormRequest;

class RejectAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'waiter';
    }

    public function rules(): array
    {
        return [
            'reason' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'Rejection reason must not exceed 500 characters',
        ];
    }
}
