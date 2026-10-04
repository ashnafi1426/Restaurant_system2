<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization should be handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:hotels,slug',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            
            // Admin user fields
            'admin_user_id' => 'nullable|uuid|exists:users,id',
            'admin_email' => 'nullable|email|max:255',
            'admin_first_name' => 'required_with:admin_email|nullable|string|max:100',
            'admin_last_name' => 'required_with:admin_email|nullable|string|max:100',
            'admin_password' => 'nullable|string|min:8',
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
            'name.required' => 'Hotel name is required.',
            'slug.required' => 'Hotel slug is required.',
            'slug.unique' => 'This hotel slug is already taken.',
            'admin_first_name.required_with' => 'Admin first name is required when creating a new admin user.',
            'admin_last_name.required_with' => 'Admin last name is required when creating a new admin user.',
            'admin_email.email' => 'Please provide a valid admin email address.',
            'admin_password.min' => 'Admin password must be at least 8 characters long.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'timezone' => $this->timezone ?? 'Africa/Addis_Ababa',
            'currency' => $this->currency ?? 'ETB',
            'status' => $this->status ?? 'active',
        ]);
    }
}