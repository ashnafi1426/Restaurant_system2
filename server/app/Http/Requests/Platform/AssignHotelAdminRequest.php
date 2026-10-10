<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class AssignHotelAdminRequest extends FormRequest
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
        return [
            'user_id' => 'nullable|uuid|exists:users,id',
            'email' => 'required_without:user_id|nullable|email|max:255',
            'first_name' => 'required_with:password|nullable|string|max:100',
            'last_name' => 'required_with:password|nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8',
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
            'email.required_without' => 'Either user ID or email is required.',
            'first_name.required_with' => 'First name is required when creating a new user with password.',
            'last_name.required_with' => 'Last name is required when creating a new user with password.',
            'password.min' => 'Password must be at least 8 characters long.',
        ];
    }
}

