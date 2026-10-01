<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QRResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QRResolutionController extends Controller
{
    public function resolveQRToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'required|string|min:1|max:100',
        ]);

        $qrToken = $validated['qr_token'];

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

    public function resolveFromUrl(string $qrToken): JsonResponse
    {
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

    public function validateQRToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'required|string|min:1|max:100',
        ]);

        $qrToken = trim($validated['qr_token']);

        $result = QRResolutionService::resolveQRToken($qrToken);

        return response()->json([
            'valid' => $result['success'],
            'context' => $result['context'],
        ], 200);
    }
}
