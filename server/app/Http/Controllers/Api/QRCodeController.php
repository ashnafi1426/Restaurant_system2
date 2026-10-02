<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class QRCodeController extends Controller
{
    /**
     * Generate QR code metadata and direct image URL for a room.
     */
    public function generateForRoom(string $roomId): JsonResponse
    {
        try {
            $room = Room::find($roomId);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'error' => 'Room not found',
                ], 404);
            }

            if (!$room->qr_token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Room has no QR token',
                ], 422);
            }

            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
            $orderUrl = "{$baseUrl}/order/{$room->qr_token}";
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($orderUrl);

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
        } catch (Throwable $e) {
            Log::error('[QR CODE] Error generating QR code', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate QR code',
            ], 500);
        }
    }

    /**
     * Generate QR codes for all active rooms belonging to the active hotel.
     */
    public function generateAll(): JsonResponse
    {
        try {
            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

            $rooms = Room::where('is_active', true)
                ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
                ->get();

            $qrCodes = [];
            $baseUrl = config('app.frontend_url', 'http://localhost:5173');

            foreach ($rooms as $room) {
                if (!$room->qr_token) {
                    continue;
                }

                $orderUrl = "{$baseUrl}/order/{$room->qr_token}";
                $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($orderUrl);

                $qrCodes[] = [
                    'room_number' => $room->room_number,
                    'qr_token' => $room->qr_token,
                    'order_url' => $orderUrl,
                    'qr_code_url' => $qrImageUrl,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'QR codes generated successfully',
                'count' => count($qrCodes),
                'qr_codes' => $qrCodes,
            ]);
        } catch (Throwable $e) {
            Log::error('[QR CODE] Error generating all QR codes', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate QR codes',
            ], 500);
        }
    }

    /**
     * Retrieve QR code metadata for a single room.
     */
    public function getQRCodeData(string $roomId): JsonResponse
    {
        try {
            $room = Room::find($roomId);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'error' => 'Room not found',
                ], 404);
            }

            if (!$room->qr_token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Room has no QR token',
                ], 422);
            }

            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
            $orderUrl = "{$baseUrl}/order/{$room->qr_token}";
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($orderUrl);

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
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve QR code data',
            ], 500);
        }
    }
}
