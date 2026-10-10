<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class WaiterTableAssignment extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'waiter_table_assignments';

    protected $fillable = [
        'hotel_id',
        'waiter_id',
        'table_id',
        'shift_id',
        'assignment_date',
        'priority',
        'status',
        'assigned_by',
    ];

    protected $casts = [
        'assignment_date' => 'date',
    ];
    public const PRIORITY_PRIMARY = 'primary';
    public const PRIORITY_SECONDARY = 'secondary';
    public const PRIORITY_BACKUP = 'backup';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_COMPLETED = 'completed';

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HotelShift::class, 'shift_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('assignment_date', today());
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('assignment_date', $date);
    }

    public function scopeForWaiter($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId);
    }

    public function scopeForTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopePrimary($query)
    {
        return $query->where('priority', self::PRIORITY_PRIMARY);
    }

    public function isPrimary(): bool
    {
        return $this->priority === self::PRIORITY_PRIMARY;
    }

    public function isSecondary(): bool
    {
        return $this->priority === self::PRIORITY_SECONDARY;
    }

    public function isBackup(): bool
    {
        return $this->priority === self::PRIORITY_BACKUP;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function markCompleted(): bool
    {
        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markInactive(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    public static function getAssignedWaiter($tableId, $shiftId = null, $date = null)
    {
        $query = self::query()
            ->with(['waiter', 'waiter.user', 'table', 'shift'])
            ->where('table_id', $tableId)
            ->active();

        if ($date) {
            $query->forDate($date);
        } else {
            $query->where(fn($q) => $q->today()->orWhereNull('assignment_date'));
        }

        if ($shiftId) {
            $query->where(function($q) use ($shiftId) {
                $q->where('shift_id', $shiftId)->orWhereNull('shift_id');
            });
        }

        return $query->orderBy('created_at', 'desc')->first();
    }

    public static function getWaiterTables($waiterId, $date = null)
    {
        $query = self::query()
            ->with(['table', 'shift'])
            ->where('waiter_id', $waiterId)
            ->active();

        if ($date) {
            $query->forDate($date);
        } else {
            $query->where(fn($q) => $q->today()->orWhereNull('assignment_date'));
        }

        return $query->get();
    }
}

