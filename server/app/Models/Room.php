<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Services\QRCodeService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        'floor' => 'integer',
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

                $room->updateQuietly([
                    'qr_image_path' => $qrImagePath,
                    'qr_generated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to generate QR code for room', [
                    'room_id' => $room->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (self::withoutGlobalScopes()->where('qr_token', $token)->exists());

        return $token;
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'floor_id', 'id');
    }

    public function hotelFloor(): BelongsTo
    {
        return $this->floor();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function activeReservation(): HasOne
    {
        return $this->hasOne(Reservation::class)
            ->where('status', 'checked_in')
            ->latestOfMany('created_at');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'room_id');
    }

    public function getCurrentReservation(): ?Reservation
    {
        $checkedIn = $this->reservations()
            ->withoutGlobalScopes()
            ->where('status', 'checked_in')
            ->with('guest')
            ->latest('created_at')
            ->first();

        if ($checkedIn) {
            return $checkedIn;
        }

        return $this->reservations()
            ->withoutGlobalScopes()
            ->with('guest')
            ->latest('created_at')
            ->first();
    }

    public function getFloorId(): ?string
    {
        if ($this->floor_id) {
            return (string) $this->floor_id;
        }

        $floorRel = $this->relationLoaded('hotelFloor') ? $this->getRelation('hotelFloor') : ($this->relationLoaded('floor') ? $this->getRelation('floor') : null);
        if ($floorRel && is_object($floorRel) && isset($floorRel->id)) {
            return (string) $floorRel->id;
        }

        $floorNum = $this->getAttribute('floor');
        if ($floorNum !== null) {
            return Floor::where('floor_number', $floorNum)
                ->when($this->hotel_id, fn($q) => $q->where('hotel_id', $this->hotel_id))
                ->value('id');
        }

        return null;
    }

    public function getQRCodeUrlAttribute(): ?string
    {
        if (!$this->qr_image_path) {
            return null;
        }

        return url("storage/{$this->qr_image_path}");
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}