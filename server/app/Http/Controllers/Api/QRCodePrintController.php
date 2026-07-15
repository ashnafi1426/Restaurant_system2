<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR Code Print/Download Controller
 * For Admin to download and print QR codes
 */
class QRCodePrintController extends Controller
{
    /**
     * Get QR code image for a room (display or download)
     * GET /api/admin/qr-codes/{roomId}/image
     */
    public function getQRCodeImage($roomId)
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found'
            ], 404);
        }
        
        // If QR image doesn't exist, regenerate it
        if (!$room->qr_image_path || !Storage::exists("public/{$room->qr_image_path}")) {
            try {
                QRCodeService::regenerateQRCode(
                    $room,
                    config('app.frontend_url', 'http://localhost:5173')
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate QR code: ' . $e->getMessage()
                ], 500);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'qr_token' => $room->qr_token,
                'qr_url' => $room->qr_code_url,  // Use the accessor
                'qr_image_path' => $room->qr_image_path,
                'qr_generated_at' => $room->qr_generated_at,
            ]
        ]);
    }
    
    /**
     * Download QR code as PNG file
     * GET /api/admin/qr-codes/{roomId}/download
     * GET /api/qr-codes/download/{roomId} (Public)
     */
    public function downloadQRCode($roomId)
    {
        $room = Room::find($roomId);
        
        if (!$room || !$room->qr_image_path) {
            return response()->json([
                'success' => false,
                'message' => 'QR code not found for this room'
            ], 404);
        }
        
        $filePath = "public/{$room->qr_image_path}";
        
        if (!Storage::exists($filePath)) {
            \Log::warning('QR code file not found', [
                'room_id' => $roomId,
                'file_path' => $filePath,
                'exists' => Storage::exists($filePath),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'QR code file not found on disk'
            ], 404);
        }
        
        try {
            $fileContent = Storage::get($filePath);
            
            if (empty($fileContent)) {
                throw new \Exception('File content is empty');
            }
            
            \Log::info('QR code download', [
                'room_id' => $roomId,
                'file_size' => strlen($fileContent),
            ]);
            
            return response($fileContent, 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="Room_' . $room->room_number . '_QR.png"')
                ->header('Content-Length', strlen($fileContent))
                ->header('Cache-Control', 'public, max-age=86400')
                ->header('Pragma', 'public')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            \Log::error('Failed to download QR code', [
                'room_id' => $roomId,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to download QR code: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Regenerate QR code for a room (if needed)
     * POST /api/admin/qr-codes/{roomId}/regenerate
     */
    public function regenerateQRCode($roomId)
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found'
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
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate QR code: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get QR codes for all rooms (for bulk download/print)
     * GET /api/admin/qr-codes/all
     */
    public function getAllQRCodes()
    {
        $rooms = Room::where('is_active', true)
            ->select('id', 'room_number', 'qr_token', 'qr_image_path', 'qr_code_url')
            ->orderBy('room_number')
            ->get();
        
        $qrCodes = $rooms->map(function ($room) {
            return [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'qr_token' => $room->qr_token,
                'qr_url' => $room->qr_code_url,
                'qr_image_path' => $room->qr_image_path,
            ];
        });
        
        return response()->json([
            'success' => true,
            'count' => count($qrCodes),
            'data' => $qrCodes
        ]);
    }
    
    /**
     * Get print template with QR code embedded (HTML)
     * GET /api/admin/qr-codes/{roomId}/print-template
     */
    public function getPrintTemplate($roomId)
    {
        $room = Room::find($roomId);
        
        if (!$room || !$room->qr_image_path) {
            return response()->json([
                'success' => false,
                'message' => 'Room or QR code not found'
            ], 404);
        }
        
        $qrUrl = $room->qr_code_url;
        
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Room {$room->room_number} - QR Code</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background-color: #f5f5f5;
                }
                .card {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    text-align: center;
                    width: 400px;
                }
                h1 {
                    margin: 0 0 10px 0;
                    color: #333;
                    font-size: 24px;
                }
                .hotel-name {
                    color: #666;
                    font-size: 14px;
                    margin-bottom: 20px;
                }
                .qr-container {
                    margin: 20px 0;
                }
                .qr-container img {
                    width: 250px;
                    height: 250px;
                    border: 2px solid #ddd;
                    padding: 10px;
                    background: white;
                }
                .info {
                    margin-top: 20px;
                    color: #666;
                    font-size: 12px;
                }
                .divider {
                    border-top: 2px dashed #ddd;
                    margin: 30px 0;
                }
                @media print {
                    body {
                        background-color: white;
                    }
                    .card {
                        box-shadow: none;
                        width: 100%;
                        max-width: 100%;
                    }
                }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>HOTEL SERVICE</h1>
                <div class="hotel-name">Room Service Order</div>
                
                <div class="qr-container">
                    <img src="{$qrUrl}" alt="QR Code for Room {$room->room_number}">
                </div>
                
                <h2 style="font-size: 20px; margin: 20px 0;">Room {$room->room_number}</h2>
                
                <div class="info">
                    <p>📱 Scan this code to order food</p>
                    <p style="margin-top: 15px; color: #999; font-size: 11px;">
                        Token: {$room->qr_token}
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
    
    /**
     * Export QR code info as JSON (for admin dashboard)
     * GET /api/admin/qr-codes/{roomId}
     */
    public function show($roomId)
    {
        return $this->getQRCodeImage($roomId);
    }
}
