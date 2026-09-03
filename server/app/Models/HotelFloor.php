<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

/**
 * HotelFloor Model
 * Represents physical floors/sections in the hotel
 */
class HotelFloor extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    protected $table = 'hotel_floors';
    
    // Configure UUID as primary key
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

    /**
     * Generate UUID on creation
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Str::uuid();
            }
        });
    }

    /**
     * Scope to get active floors
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get rooms in this floor
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'floor_id');
    }

    /**
     * Get waiter assignments for this floor
     */
    public function waiterAssignments(): HasMany
    {
        return $this->hasMany(WaiterFloorAssignment::class, 'floor_id');
    }

    /**
     * Get active assignments for this floor (alias for destroy check)
     */
    public function assignments(): HasMany
    {
        return $this->waiterAssignments();
    }

    /**
     * Get waiters assigned to this floor (for eager loading)
     */
    public function waiters()
    {
        return $this->hasManyThrough(
            Waiter::class,
            WaiterFloorAssignment::class,
            'floor_id',  // Foreign key on WaiterFloorAssignment
            'id',        // Foreign key on Waiter
            'id',        // Local key on HotelFloor
            'waiter_id'  // Local key on WaiterFloorAssignment
        )->where('waiter_floor_assignments.status', 'active');
    }

    /**
     * Get delivery tasks for this floor
     */
    public function deliveryTasks(): HasMany
    {
        return $this->hasMany(DeliveryTask::class, 'floor_id');
    }

    /**
     * Get primary waiter for a specific shift today
     */
    public function getPrimaryWaiterForShift($shiftId)
    {
        return $this->waiterAssignments()
            ->where('shift_id', $shiftId)
            ->where('assignment_date', now()->toDateString())
            ->where('priority', 'primary')
            ->where('status', 'active')
            ->first()?->waiter;
    }

    /**
     * Get available waiters for this floor (ordered by priority)
     */
    public function getAvailableWaiters($shiftId)
    {
        return $this->waiterAssignments()
            ->where('shift_id', $shiftId)
            ->where('assignment_date', now()->toDateString())
            ->where('status', 'active')
            ->orderBy('priority', 'asc') // primary first
            ->get()
            ->pluck('waiter')
            ->filter(fn($w) => $w && $w->isAvailable());
    }
}
