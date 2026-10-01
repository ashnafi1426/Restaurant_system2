<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use App\Models\Traits\BelongsToTenant;

class HotelShift extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    protected $table = 'hotel_shifts';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'hotel_id',
        'name',
        'start_time',
        'end_time',
        'status',
        'description',
    ];
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Str::uuid();
            }
        });
    }
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public static function getCurrentShift(): ?self
    {
        $shifts = self::active()->get();
        
        foreach ($shifts as $shift) {
            if ($shift->isCurrentShift()) {
                return $shift;
            }
        }
        
        return null;
    }
    
    public function waiterAssignments(): HasMany
    {
        return $this->hasMany(WaiterFloorAssignment::class, 'shift_id');
    }
    public function isCurrentShift(): bool
    {
        $now = now()->format('H:i');
        $start = $this->start_time->format('H:i');
        $end = $this->end_time->format('H:i');

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        } else {
            return $now >= $start || $now <= $end;
        }
    }
    public function getDurationInHours(): float
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    public static function seedDefaultShiftsForHotel(?string $hotelId = null): void
    {
        $hotelIds = [];
        if ($hotelId) {
            $hotelIds[] = $hotelId;
        } else {
            $hotelIds = \App\Models\Hotel::pluck('id')->toArray();
        }

        if (empty($hotelIds)) {
            return;
        }

        $defaultShifts = [
            [
                'name' => 'Morning',
                'start_time' => '06:00',
                'end_time' => '14:00',
                'status' => 'active',
                'description' => 'Morning shift',
            ],
            [
                'name' => 'Afternoon',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'status' => 'active',
                'description' => 'Afternoon shift',
            ],
            [
                'name' => 'Evening',
                'start_time' => '17:00',
                'end_time' => '23:00',
                'status' => 'active',
                'description' => 'Evening shift',
            ],
            [
                'name' => 'Night',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'status' => 'active',
                'description' => 'Night shift',
            ],
        ];

        foreach ($hotelIds as $hId) {
            $hasShifts = self::withoutTenant()->where('hotel_id', $hId)->exists();
            if (!$hasShifts) {
                foreach ($defaultShifts as $shiftData) {
                    try {
                        self::withoutTenant()->create([
                            'id' => \Illuminate\Support\Str::uuid(),
                            'hotel_id' => $hId,
                            'name' => $shiftData['name'],
                            'start_time' => $shiftData['start_time'],
                            'end_time' => $shiftData['end_time'],
                            'status' => $shiftData['status'],
                            'description' => $shiftData['description'],
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning("seedDefaultShiftsForHotel error: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
