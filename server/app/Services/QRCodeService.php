<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QRCodeService
{
    public static function generateAndSaveQRCode($roomId, $roomNumber, $qrToken, $baseUrl = 'http://localhost:5173')
    {
        try {
            $url = "{$baseUrl}/order/{$qrToken}";
            $storageDir = storage_path('app/public/qr-codes');
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            
            $filename = "room_{$roomNumber}.png";
            $filePath = $storageDir . '/' . $filename;
            
            try {
                $qrCode = QrCode::format('png')
                    ->size(300)  // 300x300 pixels for good quality
                    ->errorCorrection('H')  // Highest error correction
                    ->generate($url);
                
                // Save the QR code directly to filesystem
                file_put_contents($filePath, $qrCode);
                
                \Log::info('QR Code saved successfully', [
                    'room_id' => $roomId,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                ]);
                
            } catch (\Exception $generationError) {
                // If generation fails, try with online API as fallback
                \Log::warning('Local QR generation failed, trying API', [
                    'error' => $generationError->getMessage(),
                ]);
                
                $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url);
                $qrImage = @file_get_contents($qrApiUrl);
                
                if ($qrImage === false) {
                    throw new \Exception('Both local and API QR code generation failed');
                }
                
                file_put_contents($filePath, $qrImage);
                
                \Log::info('QR Code saved from API', [
                    'room_id' => $roomId,
                    'path' => $filePath,
                ]);
            }
            
            // Return the path for storage retrieval
            return "qr-codes/{$filename}";
            
        } catch (\Exception $e) {
            \Log::error('QR Code Generation Error', [
                'room_id' => $roomId,
                'room_number' => $roomNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    public static function getQRCodeUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }
        return url("storage/{$imagePath}");
    }
    public static function regenerateQRCode($room, $baseUrl = 'http://localhost:5173')
    {
        if ($room->qr_image_path) {
            Storage::delete("public/{$room->qr_image_path}");
        }
        
        $newPath = self::generateAndSaveQRCode(
            $room->id,
            $room->room_number,
            $room->qr_token,
            $baseUrl
        );
        
        $room->update([
            'qr_image_path' => $newPath,
            'qr_generated_at' => now(),
        ]);
        
        return $newPath;
    }
    public static function deleteQRCode($imagePath)
    {
        if ($imagePath && Storage::exists("public/{$imagePath}")) {
            return Storage::delete("public/{$imagePath}");
        }
        return true;
    }
}
