<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QRResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QRResolutionController extends Controller
{
    /**
     * Resolve QR token and return context information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resolveQRToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'required|string|min:1|max:100',
        ]);

        $qrToken = $validated['qr_token']; // Don't uppercase if it contains dashes

        $result = QRResolutionService::resolveQRToken($qrToken);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'context' => $result['context'],
            'data' => $result['data'],
            'message' => $result['message'],
        ], 200);
    }

    /**
     * Resolve QR token from URL parameter
     * 
     * @param string $qrToken
     * @return JsonResponse
     */
    public function resolveFromUrl(string $qrToken): JsonResponse
    {
        // Don't uppercase if it contains dashes (legacy format)
        $result = QRResolutionService::resolveQRToken($qrToken);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'context' => $result['context'],
            'data' => $result['data'],
            'message' => $result['message'],
        ], 200);
    }

    /**
     * Check if QR token is valid and active (lightweight check)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateQRToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'required|string|size:8',
        ]);

        $qrToken = strtoupper($validated['qr_token']);

        $result = QRResolutionService::resolveQRToken($qrToken);

        return response()->json([
            'valid' => $result['success'],
            'context' => $result['context'],
        ], 200);
    }
}
