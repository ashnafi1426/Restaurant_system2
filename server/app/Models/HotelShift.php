<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
class HotelShift extends Model
{
    use HasFactory;
    protected $table = 'hotel_shifts';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
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
            // Normal shift (e.g., 09:00 to 17:00)
            return $now >= $start && $now <= $end;
        } else {
            // Midnight-crossing shift (e.g., 22:00 to 06:00)
            return $now >= $start || $now <= $end;
        }
    }
    public function getDurationInHours(): float
    {
        return $this->start_time->diffInHours($this->end_time);
    }
}
