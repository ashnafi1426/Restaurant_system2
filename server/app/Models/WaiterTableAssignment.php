<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WaiterTableAssignment Model
 * 
 * Manages the assignment of waiters to restaurant tables for walk-in customer service.
 * This allows managers to assign specific waiters to specific tables during specific shifts.
 * 
 * Database Relationships:
 * - waiter_id (bigint) -> waiters.id
 * - table_id (uuid) -> restaurant_tables.id
 * - shift_id (uuid) -> hotel_shifts.id
 * - assigned_by (uuid) -> users.id
 * 
 * Use Cases:
 * 1. Manager assigns waiter to table for a specific shift
 * 2. Walk-in customer scans table QR code
 * 3. System finds assigned waiter for that table + current shift
 * 4. Order is automatically assigned to the waiter
 */
class WaiterTableAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
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

    /*
    |--------------------------------------------------------------------------
    | Priority Constants
    |--------------------------------------------------------------------------
    */
    public const PRIORITY_PRIMARY = 'primary';
    public const PRIORITY_SECONDARY = 'secondary';
    public const PRIORITY_BACKUP = 'backup';

    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_COMPLETED = 'completed';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the waiter assigned to this table
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class);
    }

    /**
     * Get the restaurant table for this assignment
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    /**
     * Get the shift for this assignment
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(HotelShift::class, 'shift_id');
    }

    /**
     * Get the manager who made this assignment
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active assignments only
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for today's assignments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('assignment_date', today());
    }

    /**
     * Scope for specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('assignment_date', $date);
    }

    /**
     * Scope for specific waiter
     */
    public function scopeForWaiter($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId);
    }

    /**
     * Scope for specific table
     */
    public function scopeForTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    /**
     * Scope for specific shift
     */
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope for primary assignments
     */
    public function scopePrimary($query)
    {
        return $query->where('priority', self::PRIORITY_PRIMARY);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is a primary assignment
     */
    public function isPrimary(): bool
    {
        return $this->priority === self::PRIORITY_PRIMARY;
    }

    /**
     * Check if this is a secondary assignment
     */
    public function isSecondary(): bool
    {
        return $this->priority === self::PRIORITY_SECONDARY;
    }

    /**
     * Check if this is a backup assignment
     */
    public function isBackup(): bool
    {
        return $this->priority === self::PRIORITY_BACKUP;
    }

    /**
     * Check if assignment is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Mark assignment as completed
     */
    public function markCompleted(): bool
    {
        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark assignment as inactive
     */
    public function markInactive(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    /**
     * Get the assigned waiter for a table at a specific time
     * 
     * @param string $tableId
     * @param string|null $shiftId
     * @param string|null $date
     * @return WaiterTableAssignment|null
     */
    public static function getAssignedWaiter($tableId, $shiftId = null, $date = null)
    {
        $query = self::query()
            ->with(['waiter', 'waiter.user', 'table', 'shift'])
            ->where('table_id', $tableId)
            ->active();

        if ($date) {
            $query->forDate($date);
        } else {
            $query->today();
        }

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        // Get primary assignment first, fallback to secondary, then backup
        return $query->orderByRaw("
            CASE 
                WHEN priority = 'primary' THEN 1
                WHEN priority = 'secondary' THEN 2
                WHEN priority = 'backup' THEN 3
                ELSE 4
            END
        ")->first();
    }

    /**
     * Get all tables assigned to a waiter
     * 
     * @param int $waiterId
     * @param string|null $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getWaiterTables($waiterId, $date = null)
    {
        $query = self::query()
            ->with(['table', 'shift'])
            ->where('waiter_id', $waiterId)
            ->active();

        if ($date) {
            $query->forDate($date);
        } else {
            $query->today();
        }

        return $query->get();
    }
}
