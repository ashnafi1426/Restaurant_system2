<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\QRResolutionService;

class QRTokenValidation extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_token' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $resolution = QRResolutionService::resolveQRToken($value);
                    if (!$resolution['success']) {
                        $fail($resolution['message'] ?? 'The QR token is invalid or expired.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'qr_token.required' => 'QR token is required for guest booking access',
            'qr_token.string' => 'QR token must be a valid string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $qrToken = $this->input('qr_token')
            ?? $this->query('qr_token')
            ?? $this->json('qr_token')
            ?? $this->header('X-QR-Token');

        if ($qrToken) {
            $this->merge(['qr_token' => $qrToken]);
        }
    }

    public function getQRResolution(): array
    {
        $resolution = QRResolutionService::resolveQRToken($this->input('qr_token'));
        return $resolution['data'] ?? [];
    }

    public function getHotelId(): ?string
    {
        return $this->getQRResolution()['hotel_id'] ?? null;
    }
}

