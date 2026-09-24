<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class WaiterFloorAssignment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'waiter_floor_assignments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'hotel_id',
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

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(HotelFloor::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HotelShift::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeToday($query)
    {
        return $query->where('assignment_date', now()->toDateString());
    }

    public function scopeForFloor($query, $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePrimary($query)
    {
        return $query->where('priority', 'primary');
    }

    public function scopeSecondary($query)
    {
        return $query->where('priority', 'secondary');
    }

    public function scopeBackup($query)
    {
        return $query->where('priority', 'backup');
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
    public function getDeliveryCount(): int
    {
        return $this->waiter->deliveryTasks()
            ->where('floor_id', $this->floor_id)
            ->where('shift_id', $this->shift_id)
            ->whereDate('assigned_at', $this->assignment_date)
            ->whereIn('status', ['delivered', 'completed'])
            ->count();
    }

    public function getPendingDeliveryCount(): int
    {
        return $this->waiter->deliveryTasks()
            ->where('floor_id', $this->floor_id)
            ->whereDate('assigned_at', $this->assignment_date)
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
            ->count();
    }
}
