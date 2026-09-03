<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\QRResolutionService;

class QRTokenValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Guest endpoints - no user authentication required
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'qr_token' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Validate QR token exists and is valid
                    $resolution = QRResolutionService::resolveQRToken($value);
                    if (!$resolution['success']) {
                        $fail($resolution['message'] ?? 'The QR token is invalid or expired.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'qr_token.required' => 'QR token is required for guest booking access',
            'qr_token.string' => 'QR token must be a valid string',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Support QR token from multiple sources
        $qrToken = $this->input('qr_token') 
            ?? $this->query('qr_token')
            ?? $this->json('qr_token')
            ?? $this->header('X-QR-Token');

        if ($qrToken) {
            $this->merge(['qr_token' => $qrToken]);
        }
    }

    /**
     * Get the QR resolution data for the validated token.
     */
    public function getQRResolution(): array
    {
        $resolution = QRResolutionService::resolveQRToken($this->input('qr_token'));
        return $resolution['data'] ?? [];
    }

    /**
     * Get the hotel ID from the QR token.
     */
    public function getHotelId(): ?string
    {
        return $this->getQRResolution()['hotel_id'] ?? null;
    }
}
