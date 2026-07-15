<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Generate and save QR code for a room
     * Uses simplesoftwareio/simple-qrcode library
     * 
     * @param int $roomId - Room ID
     * @param int $roomNumber - Room number (for display)
     * @param string $qrToken - The QR token for the room
     * @param string $baseUrl - Base URL (e.g., https://hotel.com)
     * @return string - Path to saved QR image
     */
    public static function generateAndSaveQRCode($roomId, $roomNumber, $qrToken, $baseUrl = 'http://localhost:5173')
    {
        try {
            // Create QR code URL
            $url = "{$baseUrl}/order/{$qrToken}";
            
            // Ensure directory exists (create physically, not via Storage facade)
            $storageDir = storage_path('app/public/qr-codes');
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            
            // Generate filename: room_205.png
            $filename = "room_{$roomNumber}.png";
            $filePath = $storageDir . '/' . $filename;
            
            // Generate QR code with high quality
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
    
    /**
     * Get full path to QR code image
     * 
     * @param string $imagePath - Stored image path
     * @return string - Full URL to the image
     */
    public static function getQRCodeUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }
        return url("storage/{$imagePath}");
    }
    
    /**
     * Regenerate QR code for a room (if token changed or needs refresh)
     * 
     * @param \App\Models\Room $room
     * @param string $baseUrl
     * @return string - New image path
     */
    public static function regenerateQRCode($room, $baseUrl = 'http://localhost:5173')
    {
        // Delete old QR code if exists
        if ($room->qr_image_path) {
            Storage::delete("public/{$room->qr_image_path}");
        }
        
        // Generate new one
        $newPath = self::generateAndSaveQRCode(
            $room->id,
            $room->room_number,
            $room->qr_token,
            $baseUrl
        );
        
        // Update room record
        $room->update([
            'qr_image_path' => $newPath,
            'qr_generated_at' => now(),
        ]);
        
        return $newPath;
    }
    
    /**
     * Delete QR code image for a room
     * 
     * @param string $imagePath
     * @return bool
     */
    public static function deleteQRCode($imagePath)
    {
        if ($imagePath && Storage::exists("public/{$imagePath}")) {
            return Storage::delete("public/{$imagePath}");
        }
        return true;
    }
}
