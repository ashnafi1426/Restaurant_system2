<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        \Log::info('🔓 [CHECK-IN-REQUEST] Authorization check', ['authorized' => true]);
        return true;
    }
    public function rules(): array
    {
        $rules = [
            'reservation_id' => [
                'required',
                'uuid',
                'exists:reservations,id',
            ],
        ];
        \Log::info('🔓 [CHECK-IN-REQUEST] Rules applied', ['rules' => $rules]);
        return $rules;
    }

    public function messages(): array
    {
        return [
            'reservation_id.required' => 'Reservation is required.',
            'reservation_id.exists' => 'Reservation not found.',
        ];
    }
}