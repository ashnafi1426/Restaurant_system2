<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }

    public function rules(): array
    {
        return [
            'assigned_to' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Staff member to assign is required',
            'assigned_to.exists' => 'Staff member not found',
        ];
    }
}

