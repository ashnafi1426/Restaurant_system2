<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\QRCodeService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class QRCodePrintController extends Controller
{
    /**
     * Get QR code data for a room (simplified version).
     */
    public function getQRCodeImage(string $roomId): JsonResponse
    {
        $room = Room::find($roomId);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'qr_token' => $room->qr_token,
                'qr_url' => $room->qr_code_url,
                'qr_image_path' => $room->qr_image_path,
                'qr_generated_at' => $room->qr_generated_at,
            ],
        ]);
    }

    /**
     * Download PNG file of a room's QR code.
     */
    public function downloadQRCode(string $roomId): Response|JsonResponse
    {
        $room = Room::find($roomId);

        if (!$room || !$room->qr_image_path) {
            return response()->json([
                'success' => false,
                'message' => 'QR code not found for this room',
            ], 404);
        }

        $filePath = "public/{$room->qr_image_path}";
        if (!Storage::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'QR code file not found on disk',
            ], 404);
        }

        try {
            $fileContent = Storage::get($filePath);
            if (empty($fileContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code file is empty',
                ], 500);
            }

            return response($fileContent, 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="Room_' . $room->room_number . '_QR.png"')
                ->header('Content-Length', strlen($fileContent))
                ->header('Cache-Control', 'public, max-age=86400');
        } catch (Throwable $e) {
            Log::error('Failed to download QR code', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force regenerate a room's QR code.
     */
    public function regenerateQRCode(string $roomId): JsonResponse
    {
        $room = Room::find($roomId);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
            ], 404);
        }

        try {
            $newPath = QRCodeService::regenerateQRCode(
                $room,
                config('app.frontend_url', 'http://localhost:5173')
            );

            return response()->json([
                'success' => true,
                'message' => 'QR code regenerated successfully',
                'data' => [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'qr_token' => $room->qr_token,
                    'qr_url' => $room->qr_code_url,
                    'qr_image_path' => $newPath,
                    'qr_generated_at' => $room->qr_generated_at,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List QR codes for rooms belonging to the active hotel.
     */
    public function getAllQRCodes(): JsonResponse
    {
        $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

        $rooms = Room::where('is_active', true)
            ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
            ->select('id', 'room_number', 'qr_token', 'qr_image_path', 'qr_code_url')
            ->orderBy('room_number')
            ->get();

        $qrCodes = $rooms->map(fn ($room) => [
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'qr_token' => $room->qr_token,
            'qr_url' => $room->qr_code_url,
            'qr_image_path' => $room->qr_image_path,
        ]);

        return response()->json([
            'success' => true,
            'count' => count($qrCodes),
            'data' => $qrCodes,
        ]);
    }

    /**
     * Generate HTML printable sheet for a room's QR code.
     */
    public function getPrintTemplate(Request $request, string $roomId): Response|JsonResponse
    {
        $room = Room::find($roomId);

        if (!$room || !$room->qr_image_path) {
            return response()->json([
                'success' => false,
                'message' => 'Room or QR code not found',
            ], 404);
        }

        $copies = min(10, max(1, (int) $request->query('copies', $request->query('quantity', 1))));
        $qrUrl = $room->qr_code_url;

        $cardsHtml = '';
        for ($i = 0; $i < $copies; $i++) {
            $cardsHtml .= <<<CARD
            <div class="card">
                <h1>HOTEL SERVICE</h1>
                <div class="hotel-name">Room Service Order</div>
                <div class="qr-container">
                    <img src="{$qrUrl}" alt="QR Code for Room {$room->room_number}">
                </div>
                <h2 style="font-size: 20px; margin: 15px 0 5px 0;">Room {$room->room_number}</h2>
                <div class="info">
                    <p>Scan this code to order food</p>
                    <p style="margin-top: 10px; color: #999; font-size: 11px;">Token: {$room->qr_token}</p>
                </div>
            </div>
            CARD;
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Room {$room->room_number} - QR Code ({$copies} copies)</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f8fafc;
                    margin: 0;
                    padding: 24px;
                    color: #0f172a;
                }
                .print-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                    gap: 20px;
                    max-width: 1200px;
                    margin: 0 auto;
                }
                .card {
                    background: white;
                    padding: 24px;
                    border-radius: 16px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
                    text-align: center;
                    border: 1px solid #e2e8f0;
                    page-break-inside: avoid;
                    break-inside: avoid;
                }
                h1 {
                    margin: 0 0 4px 0;
                    color: #0f172a;
                    font-size: 18px;
                    font-weight: 900;
                    letter-spacing: 0.5px;
                }
                .hotel-name {
                    color: #64748b;
                    font-size: 12px;
                    margin-bottom: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .qr-container { margin: 12px 0; }
                .qr-container img {
                    width: 170px;
                    height: 170px;
                    border: 1px solid #cbd5e1;
                    padding: 8px;
                    background: white;
                    border-radius: 12px;
                }
                .info {
                    margin-top: 12px;
                    color: #475569;
                    font-size: 12px;
                    font-weight: 600;
                }
                @media print {
                    body { background-color: white; padding: 0; }
                    .print-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 16px;
                        max-width: 100%;
                    }
                    .card { box-shadow: none; border: 1px solid #cbd5e1; }
                }
            </style>
        </head>
        <body>
            <div class="print-grid">
                {$cardsHtml}
            </div>
        </body>
        </html>
        HTML;

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function show(string $roomId): JsonResponse
    {
        return $this->getQRCodeImage($roomId);
    }
}

