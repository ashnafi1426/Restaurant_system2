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
        \Log::info('[CHECK-IN-REQUEST] Validating request data', [
            'all_data' => $this->all(),
            'has_reservation_id' => $this->has('reservation_id'),
            'reservation_id_value' => $this->input('reservation_id'),
        ]);
        
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
            'reservation_id.uuid' => 'Reservation ID must be a valid UUID.',
            'reservation_id.exists' => 'Reservation not found in database.',
        ];
    }
    
    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Log::error('[CHECK-IN-REQUEST] Validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->all(),
        ]);
        
        parent::failedValidation($validator);
    }
}