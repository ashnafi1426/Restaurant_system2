<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\QRResolutionService;
use App\Services\TenantContext;
use App\Models\Hotel;
use Illuminate\Support\Facades\Log;

class QRTokenMiddleware
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Handle the incoming request by validating QR token and setting tenant context.
     * 
     * Extraction sources (in order of priority):
     * 1. Route parameter: {qrToken}
     * 2. Query parameter: ?qr_token=...
     * 3. Request body: JSON body with qr_token field
     * 4. Header: X-QR-Token header
     * 5. Request body: form-data qr_token
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extract QR token from multiple sources
        $qrToken = $this->extractQRToken($request);

        // 2. If no token found, return 401
        if (!$qrToken) {
            Log::warning('QR token extraction failed', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'QR token is required for guest booking access',
            ], 401);
        }

        // 3. Validate/resolve QR token using QRResolutionService
        $resolution = QRResolutionService::resolveQRToken($qrToken);

        if (!$resolution['success']) {
            Log::warning('QR token validation failed', [
                'qr_token' => $qrToken,
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'reason' => $resolution['message'],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => $resolution['message'] ?? 'Invalid QR token',
            ], 401);
        }

        // 4. Determine hotel from resolution data
        $hotelId = $resolution['data']['hotel_id'] ?? null;

        if (!$hotelId) {
            Log::error('QR token resolved but hotel_id missing', [
                'qr_token' => $qrToken,
                'resolution_data' => $resolution['data'],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server Error',
                'message' => 'Unable to determine hotel from QR token',
            ], 500);
        }

        // 5. Verify hotel exists and is active
        $hotel = Hotel::find($hotelId);

        if (!$hotel || !$hotel->isActive()) {
            Log::warning('Hotel not found or inactive', [
                'hotel_id' => $hotelId,
                'qr_token' => $qrToken,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Hotel is not available',
            ], 403);
        }

        // 6. Set TenantContext with hotel and no membership (guest context)
        $this->tenantContext->setHotel($hotel, null);

        // 7. Attach resolution data to request for downstream use
        $request->attributes->set('qr_resolution', $resolution);
        $request->attributes->set('qr_token', $qrToken);
        $request->attributes->set('guest_hotel_id', $hotelId);

        Log::info('QR token validated successfully', [
            'qr_token' => $qrToken,
            'hotel_id' => $hotelId,
            'context' => $resolution['context'],
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Extract QR token from multiple sources in priority order.
     * 
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function extractQRToken(Request $request): ?string
    {
        // 1. Try route parameter: {qrToken}
        $token = $request->route('qrToken');
        if ($token) {
            return $token;
        }

        // 2. Try query parameter: ?qr_token=...
        $token = $request->query('qr_token');
        if ($token) {
            return $token;
        }

        // 3. Try JSON body: {"qr_token": "..."}
        $token = $request->json('qr_token');
        if ($token) {
            return $token;
        }

        // 4. Try header: X-QR-Token
        $token = $request->header('X-QR-Token');
        if ($token) {
            return $token;
        }

        // 5. Try form-data: qr_token in POST body
        $token = $request->input('qr_token');
        if ($token) {
            return $token;
        }

        return null;
    }
}
