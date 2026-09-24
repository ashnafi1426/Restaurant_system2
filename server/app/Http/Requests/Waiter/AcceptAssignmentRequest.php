<?php

namespace App\Http\Requests\Waiter;

use Illuminate\Foundation\Http\FormRequest;

class AcceptAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'waiter';
    }

    public function rules(): array
    {
        return [
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
