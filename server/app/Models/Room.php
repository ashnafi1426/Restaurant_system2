<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\QRCodeService;
use Illuminate\Support\Str;

class Room extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_number',
        'room_type_id',
        'floor',
        'description',
        'status',
        'is_active',
        'qr_token',
        'qr_image_path',
        'qr_generated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'qr_generated_at' => 'datetime',
    ];
    
    /**
     * Boot model to auto-generate QR token and code when creating
     */
    protected static function booted()
    {
        static::creating(function ($room) {
            // Generate random 8-character token if not set
            if (!$room->qr_token) {
                $room->qr_token = self::generateUniqueToken();
            }
        });
        
        static::created(function ($room) {
            // Generate QR code image after room is created
            try {
                $qrImagePath = QRCodeService::generateAndSaveQRCode(
                    $room->id,
                    $room->room_number,
                    $room->qr_token,
                    config('app.frontend_url', 'http://localhost:5173')
                );
                
                $room->update([
                    'qr_image_path' => $qrImagePath,
                    'qr_generated_at' => now(),
                ]);
                
                \Log::info('QR Code Generated for Room', [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'token' => $room->qr_token,
                    'image_path' => $qrImagePath,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to generate QR code for room', [
                    'room_id' => $room->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
    
    /**
     * Generate unique 8-character token
     */
    public static function generateUniqueToken()
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (self::where('qr_token', $token)->exists());
        
        return $token;
    }
    
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
    
    /**
     * Get full QR code image URL
     */
    public function getQRCodeUrlAttribute()
    {
        if (!$this->qr_image_path) {
            return null;
        }
        return url("storage/{$this->qr_image_path}");
    }

    /**
     * Scope to search rooms by multiple criteria
     * Searches: room_number, floor, description, status, room_type name
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (!$searchTerm) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->whereRaw('LOWER(room_number) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(status) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhere('floor', 'LIKE', '%' . $searchTerm . '%')
              ->orWhereHas('roomType', function ($query) use ($searchTerm) {
                  $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
              });
        });
    }
}