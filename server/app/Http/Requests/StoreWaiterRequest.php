<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWaiterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|uuid|exists:users,id',
            'section' => 'required|string|max:50',
            'status' => 'required|in:active,inactive,on_break',
            'shift' => 'required|in:morning,afternoon,evening,night',
            'experience_level' => 'required|in:junior,senior,head',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
        ];
    }
}
