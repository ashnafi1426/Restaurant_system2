<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChapaService
{
    protected string $secretKey;
    protected string $baseUrl;
    public function __construct()
    {
        $this->secretKey = config('chapa.secret_key');
        $this->baseUrl   = config('chapa.base_url');
    }
    public function generateTransactionReference(): string
    {
        return 'TX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8));
    }
    public function initialize(array $data): array
    {
        try {
            $title = $data['title'] ?? 'Hotel Payment';
            if (strlen($title) > 16) {
                $title = substr($title, 0, 16);
                Log::warning('Chapa title truncated to 16 characters', [
                    'original' => $data['title'] ?? null,
                    'truncated' => $title,
                ]);
            }
              $payload = [
                'amount'       => (int)$data['amount'],
                'currency'     => $data['currency'] ?? 'ETB',
                'email'        => (string)$data['email'],
                'first_name'   => (string)$data['first_name'],
                'last_name'    => (string)$data['last_name'],
                'phone_number' => (string)$data['phone'],
                'tx_ref'       => (string)$data['tx_ref'],
                'callback_url' => (string)$data['callback_url'],
                'return_url'   => (string)$data['return_url'],
                'customization' => [
                    'title'       => $title,
                    'description' => $data['description'] ?? 'Hotel Payment',
                ],
            ];
            
            if (!empty($data['meta'])) {
                $payload['meta'] = $data['meta'];
            }

            Log::info('Chapa Initialize Starting', [
                'amount'   => $payload['amount'],
                'email'    => $payload['email'],
                'tx_ref'   => $payload['tx_ref'],
                'title'    => $title,
                'title_length' => strlen($title),
                'base_url' => $this->baseUrl,
                'has_secret' => !empty($this->secretKey),
            ]);

            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->withoutVerifying()
                ->post("{$this->baseUrl}/transaction/initialize", $payload);

            Log::info('Chapa Raw Response', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'json'   => $response->json(),
            ]);

            if ($response->successful()) {
                $jsonResponse = $response->json();
                
                if (isset($jsonResponse['status']) && $jsonResponse['status'] === 'success') {
                    Log::info('Chapa Initialize Success', [
                        'has_checkout_url' => isset($jsonResponse['data']['checkout_url']),
                        'checkout_url' => $jsonResponse['data']['checkout_url'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'data'    => $jsonResponse,
                    ];
                }
                
                Log::warning('Chapa HTTP 200 but status not success', [
                    'response_status' => $jsonResponse['status'] ?? null,
                    'message' => $jsonResponse['message'] ?? null,
                ]);

                return [
                    'success' => false,
                    'message' => $jsonResponse['message'] ?? 'Payment initialization failed',
                    'errors'  => $jsonResponse,
                ];
            }

            $jsonResponse = $response->json();
            Log::error('Chapa HTTP Error Response', [
                'status' => $response->status(),
                'response_json' => $jsonResponse,
            ]);

            return [
                'success' => false,
                'message' => $jsonResponse['message'] ?? 'Payment initialization failed',
                'errors'  => $jsonResponse,
            ];

        } catch (\Exception $e) {
            Log::error('Chapa Initialize Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function verify(string $txRef): array
    {
        try {

            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->withoutVerifying()
                ->get($this->baseUrl . "/transaction/verify/{$txRef}");

            Log::info('Chapa Verify Response', [

                'status' => $response->status(),

                'body' => $response->json()

            ]);

            if ($response->successful()) {

                return [

                    'success' => true,

                    'data' => $response->json()

                ];

            }

            return [

                'success' => false,

                'message' => $response->json()['message'] ?? 'Verification failed',

                'errors' => $response->json()

            ];

        } catch (\Exception $e) {

            Log::error('Chapa Verify Error', [

                'message' => $e->getMessage()

            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getCheckoutUrl(array $initializeResponse): ?string
    {
        Log::info('getCheckoutUrl called', [
            'response_structure' => [
                'has_data' => isset($initializeResponse['data']),
                'data_is_array' => is_array($initializeResponse['data'] ?? null),
                'data_keys' => array_keys($initializeResponse['data'] ?? []),
            ]
        ]);
        
        if (isset($initializeResponse['data']['checkout_url'])) {
            Log::info('Found checkout_url at data.checkout_url');
            return $initializeResponse['data']['checkout_url'];
        }
        
        if (isset($initializeResponse['data']['data']['checkout_url'])) {
            Log::info('Found checkout_url at data.data.checkout_url');
            return $initializeResponse['data']['data']['checkout_url'];
        }
        
        Log::warning('Checkout URL not found in response', [
            'response' => $initializeResponse
        ]);
        
        return null;
    }

    public function isSuccessful(array $verifyResponse): bool
    {
        return

            isset($verifyResponse['data']['status'])

            &&

            $verifyResponse['data']['status'] === 'success';
    }

    public function getTransactionId(array $verifyResponse): ?string
    {
        return

            $verifyResponse['data']['data']['id']

            ??

            null;
    }

    public function getPaymentMethod(array $verifyResponse): ?string
    {
        return

            $verifyResponse['data']['data']['method']

            ??

            null;
    }

    public function getAmount(array $verifyResponse): ?float
    {
        return

            $verifyResponse['data']['data']['amount']

            ??

            null;
    }

    public function getCurrency(array $verifyResponse): ?string
    {
        return

            $verifyResponse['data']['data']['currency']

            ??

            null;
    }
    public function getCustomerEmail(array $verifyResponse): ?string
    {
        return

            $verifyResponse['data']['data']['email']

            ??

            null;
    }
    public function raw(array $response): array
    {
        return $response;
    }
}