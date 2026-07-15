<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QRCodeController extends Controller
{
    /**
     * Generate QR code for a specific room using QR server API
     * 
     * Route: GET /api/qr-code/generate/{roomId}
     * Returns: QR code image URL
     */
    public function generateForRoom($roomId)
    {
        try {
            Log::info('[QR CODE] Generating QR code for room', ['room_id' => $roomId]);

            $room = Room::find($roomId);
            
            if (!$room) {
                return response()->json(['error' => 'Room not found'], 404);
            }

            if (!$room->qr_token) {
                return response()->json(['error' => 'Room has no QR token'], 422);
            }

            // Generate URL for the ordering page
            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
            $orderUrl = "{$baseUrl}/order/{$room->qr_token}";

            Log::info('[QR CODE] Generated URL', ['url' => $orderUrl]);

            // Use QR Server API (free, no dependencies)
            $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($orderUrl);

            return response()->json([
                'success' => true,
                'data' => [
                    'room_number' => $room->room_number,
                    'qr_token' => $room->qr_token,
                    'order_url' => $orderUrl,
                    'qr_code_url' => $qrImageUrl,
                    'qr_code_image_tag' => "<img src=\"{$qrImageUrl}\" alt=\"QR Code for Room {$room->room_number}\" />",
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[QR CODE] Error generating QR code', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to generate QR code'], 500);
        }
    }

    /**
     * Generate QR codes for all rooms
     * 
     * Route: GET /api/qr-code/generate-all
     * Returns: JSON with room numbers and QR code URLs
     */
    public function generateAll()
    {
        try {
            Log::info('[QR CODE] Generating QR codes for all rooms');

            $rooms = Room::where('is_active', true)->get();
            $qrCodes = [];
            $baseUrl = config('app.frontend_url', 'http://localhost:5173');

            foreach ($rooms as $room) {
                if (!$room->qr_token) {
                    continue;
                }

                $orderUrl = "{$baseUrl}/order/{$room->qr_token}";
                $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($orderUrl);

                $qrCodes[] = [
                    'room_number' => $room->room_number,
                    'qr_token' => $room->qr_token,
                    'order_url' => $orderUrl,
                    'qr_code_url' => $qrImageUrl,
                ];

                Log::info('[QR CODE] Generated QR for room', [
                    'room_number' => $room->room_number,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR codes generated successfully',
                'count' => count($qrCodes),
                'qr_codes' => $qrCodes,
            ]);

        } catch (\Exception $e) {
            Log::error('[QR CODE] Error generating all QR codes', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to generate QR codes'], 500);
        }
    }

    /**
     * Get QR code data (room info + token + QR image)
     * 
     * Route: GET /api/qr-code/data/{roomId}
     * Returns: Complete QR code info
     */
    public function getQRCodeData($roomId)
    {
        try {
            $room = Room::find($roomId);
            
            if (!$room) {
                return response()->json(['error' => 'Room not found'], 404);
            }

            if (!$room->qr_token) {
                return response()->json(['error' => 'Room has no QR token'], 422);
            }

            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
            $orderUrl = "{$baseUrl}/order/{$room->qr_token}";
            $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($orderUrl);

            return response()->json([
                'success' => true,
                'data' => [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'qr_token' => $room->qr_token,
                    'order_url' => $orderUrl,
                    'qr_code_url' => $qrImageUrl,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve QR code data'], 500);
        }
    }
}
