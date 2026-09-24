<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

class HotelFloor extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    protected $table = 'hotel_floors';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'hotel_id',
        'floor_number',
        'name',
        'description',
        'is_active',
        'total_rooms',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'floor_number' => 'integer',
        'total_rooms' => 'integer',
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
        return $query->where('is_active', true);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'floor_id');
    }

    public function waiterAssignments(): HasMany
    {
        return $this->hasMany(WaiterFloorAssignment::class, 'floor_id');
    }

    public function assignments(): HasMany
    {
        return $this->waiterAssignments();
    }

    public function waiters()
    {
        return $this->hasManyThrough(
            Waiter::class,
            WaiterFloorAssignment::class,
            'floor_id',
            'id',
            'id',
            'waiter_id'
        )->where('waiter_floor_assignments.status', 'active');
    }

    public function deliveryTasks(): HasMany
    {
        return $this->hasMany(DeliveryTask::class, 'floor_id');
    }

    public function getPrimaryWaiterForShift($shiftId)
    {
        return $this->waiterAssignments()
            ->where('shift_id', $shiftId)
            ->where('assignment_date', now()->toDateString())
            ->where('priority', 'primary')
            ->where('status', 'active')
            ->first()?->waiter;
    }

    public function getAvailableWaiters($shiftId)
    {
        return $this->waiterAssignments()
            ->where('shift_id', $shiftId)
            ->where('assignment_date', now()->toDateString())
            ->where('status', 'active')
            ->orderBy('priority', 'asc')
            ->get()
            ->pluck('waiter')
            ->filter(fn($w) => $w && $w->isAvailable());
    }
}
