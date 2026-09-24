<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitializePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount'        => 'required|numeric|min:0.01|max:999999.99',
            'currency'      => 'nullable|string|size:3|in:ETB',
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'required|string|max:20',
            'title'         => 'nullable|string|max:255',
            'description'   => 'nullable|string|max:500',
            'metadata'      => 'nullable|array',
            'reservation_id' => 'nullable|uuid|exists:reservations,id',
            'order_id'       => 'nullable|uuid|exists:orders,id',
            'guest_id'       => 'nullable|uuid|exists:guests,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'      => 'Amount is required',
            'amount.numeric'       => 'Amount must be a valid number',
            'amount.min'           => 'Amount must be greater than 0',
            'amount.max'           => 'Amount exceeds maximum allowed',
            'first_name.required'  => 'First name is required',
            'first_name.max'       => 'First name cannot exceed 255 characters',
            'last_name.required'   => 'Last name is required',
            'last_name.max'        => 'Last name cannot exceed 255 characters',
            'email.required'       => 'Email is required',
            'email.email'          => 'Email must be valid',
            'phone.required'       => 'Phone number is required',
            'phone.max'            => 'Phone number cannot exceed 20 characters',
            'reservation_id.uuid'  => 'Invalid reservation ID format',
            'reservation_id.exists' => 'Reservation not found',
            'order_id.uuid'        => 'Invalid order ID format',
            'order_id.exists'      => 'Order not found',
            'guest_id.uuid'        => 'Invalid guest ID format',
            'guest_id.exists'      => 'Guest not found',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim($this->first_name ?? ''),
            'last_name'  => trim($this->last_name ?? ''),
            'email'      => trim(strtolower($this->email ?? '')),
            'phone'      => preg_replace('/\s+/', '', $this->phone ?? ''),
        ]);
    }
}
