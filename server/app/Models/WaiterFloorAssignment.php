<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WaiterFloorAssignment Model
 * Represents daily floor assignments for waiters
 * Links: Waiter -> Floor -> Shift on a specific date
 */
class WaiterFloorAssignment extends Model
{
    use HasFactory;

    protected $table = 'waiter_floor_assignments';
    
    // UUID is primary key, not auto-incrementing
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'waiter_id',
        'floor_id',
        'shift_id',
        'assignment_date',
        'status',
        'priority',
        'assigned_by',
    ];

    protected $casts = [
        'assignment_date' => 'date',
    ];

    /**
     * Get the waiter
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class);
    }

    /**
     * Get the floor
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(HotelFloor::class);
    }

    /**
     * Get the shift
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(HotelShift::class);
    }

    /**
     * Get the manager who assigned
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get active assignments today
     */
    public function scopeToday($query)
    {
        return $query->where('assignment_date', now()->toDateString());
    }

    /**
     * Scope to get assignments for a specific floor
     */
    public function scopeForFloor($query, $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    /**
     * Scope to get assignments for a specific shift
     */
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope to get active assignments only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get primary waiter
     */
    public function scopePrimary($query)
    {
        return $query->where('priority', 'primary');
    }

    /**
     * Scope to get secondary waiters
     */
    public function scopeSecondary($query)
    {
        return $query->where('priority', 'secondary');
    }

    /**
     * Scope to get backup waiters
     */
    public function scopeBackup($query)
    {
        return $query->where('priority', 'backup');
    }

    /**
     * Mark assignment as completed
     */
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Cancel assignment
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Get delivery count for this assignment
     */
    public function getDeliveryCount(): int
    {
        return $this->waiter->deliveryTasks()
            ->where('floor_id', $this->floor_id)
            ->where('shift_id', $this->shift_id)
            ->whereDate('assigned_at', $this->assignment_date)
            ->whereIn('status', ['delivered', 'completed'])
            ->count();
    }

    /**
     * Get pending delivery count
     */
    public function getPendingDeliveryCount(): int
    {
        return $this->waiter->deliveryTasks()
            ->where('floor_id', $this->floor_id)
            ->whereDate('assigned_at', $this->assignment_date)
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
            ->count();
    }
}
