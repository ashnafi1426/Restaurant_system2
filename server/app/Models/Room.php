<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\QRCodeService;
use Illuminate\Support\Str;
use App\Models\Traits\BelongsToTenant;

class Room extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'hotel_id',
        'room_number',
        'room_type_id',
        'floor',
        'floor_id',
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
        'floor_id' => 'string',
    ];
    
    protected static function booted()
    {
        static::creating(function ($room) {
            if (!$room->qr_token) {
                $room->qr_token = self::generateUniqueToken();
            }
        });
        static::created(function ($room) {
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
    
    public function floor()
    {
        return $this->belongsTo(Floor::class, 'floor_id', 'id');
    }

    public function hotelFloor()
    {
        return $this->belongsTo(Floor::class, 'floor_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function activeReservation()
    {
        return $this->hasOne(Reservation::class)
            ->where('status', 'checked_in')
            ->latestOfMany('created_at');
    }

    public function getCurrentReservation(): ?Reservation
    {
        // 1. Check for active checked-in reservation first
        $checkedIn = Reservation::withoutGlobalScopes()
            ->where('room_id', $this->id)
            ->when($this->hotel_id, fn($q) => $q->where('hotel_id', $this->hotel_id))
            ->where('status', 'checked_in')
            ->with('guest')
            ->latest('created_at')
            ->first();

        if ($checkedIn) {
            return $checkedIn;
        }

        // 2. Fallback to latest reservation to determine status (confirmed, checked_out, cancelled, etc.)
        return Reservation::withoutGlobalScopes()
            ->where('room_id', $this->id)
            ->when($this->hotel_id, fn($q) => $q->where('hotel_id', $this->hotel_id))
            ->with('guest')
            ->latest('created_at')
            ->first();
    }
    
    public function getQRCodeUrlAttribute()
    {
        if (!$this->qr_image_path) {
            return null;
        }
        return url("storage/{$this->qr_image_path}");
    }

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

    public function getFloorId(): ?string
    {
        if ($this->floor_id) {
            return $this->floor_id;
        }

        if ($this->hotelFloor) {
            return $this->hotelFloor->id;
        }

        if ($this->floor) {
            $hotelFloor = HotelFloor::where('floor_number', $this->floor)
                                    ->orWhere('name', $this->floor)
                                    ->first();
            if ($hotelFloor) {
                return $hotelFloor->id;
            }
        }

        return null;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}