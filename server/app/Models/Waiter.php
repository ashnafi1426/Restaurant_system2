<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Waiter extends Model
{
    use HasFactory;
    protected $table = 'waiters';
    // Override keyType since we use auto-increment int, not UUID
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'employee_number',
        'phone',
        'section',
        'shift',
        'experience_level',
        'employment_type',
        'hire_date',
        'status',
        'availability',
        'current_orders',
        'maximum_orders',
        'profile_photo',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'current_orders' => 'integer',
        'maximum_orders' => 'integer',
    ];

    /**
     * Get the user associated with this waiter
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function floorAssignments(): HasMany
    {
        return $this->hasMany(WaiterFloorAssignment::class);
    }

    /**
     * Get delivery tasks
     */
    public function deliveryTasks(): HasMany
    {
        return $this->hasMany(DeliveryTask::class);
    }

    /**
     * Get waiter notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(WaiterNotification::class, 'waiter_id', 'id');
    }

    /**
     * Get all assignments for this waiter (legacy support)
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(WaiterAssignment::class, 'waiter_id', 'id');
    }

    /**
     * Get performance metrics for this waiter
     */
    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(WaiterPerformance::class, 'waiter_id', 'user_id');
    }

    /**
     * Scope to get active waiters
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get available waiters
     */
    public function scopeAvailable($query)
    {
        return $query->where('availability', 'available');
    }

    /**
     * Scope to get waiters with capacity (not at max orders)
     */
    public function scopeWithCapacity($query)
    {
        return $query->whereRaw('current_orders < maximum_orders');
    }

    /**
     * Scope to get full-time waiters
     */
    public function scopeFullTime($query)
    {
        return $query->where('employment_type', 'full_time');
    }

    /**
     * Scope for waiters on break
     */
    public function scopeOnBreak($query)
    {
        return $query->where('status', 'on_break');
    }

    /**
     * Check if waiter is available (can take orders)
     */
    public function isAvailable(): bool
    {
        return $this->status === 'active' &&
               $this->availability === 'available' &&
               $this->current_orders < $this->maximum_orders;
    }

    /**
     * Check if waiter can take more orders
     */
    public function canTakeOrders(): bool
    {
        return $this->current_orders < $this->maximum_orders;
    }

    /**
     * Check if waiter is on break
     */
    public function isOnBreak(): bool
    {
        return $this->availability === 'break';
    }

    /**
     * Check if waiter is offline
     */
    public function isOffline(): bool
    {
        return $this->availability === 'offline';
    }

    /**
     * Increment current orders (concurrency safe).
     * Handles maximum_orders = 0 as "unlimited capacity".
     */
    public function incrementOrders(): bool
    {
        // When maximum_orders = 0, treat as unlimited (no cap check)
        $updated = \Illuminate\Support\Facades\DB::table($this->getTable())
            ->where('id', $this->id)
            ->where(function ($q) {
                $q->where('maximum_orders', 0) // unlimited
                  ->orWhereRaw('current_orders < maximum_orders');
            })
            ->increment('current_orders');

        if ($updated) {
            $this->current_orders++;
            return true;
        }
        return false;
    }

    /**
     * Decrement current orders (concurrency safe)
     */
    public function decrementOrders(): bool
    {
        $updated = \Illuminate\Support\Facades\DB::table($this->getTable())
            ->where('id', $this->id)
            ->where('current_orders', '>', 0)
            ->decrement('current_orders');
            
        if ($updated) {
            $this->current_orders--;
            return true;
        }
        return false;
    }

    /**
     * Set waiter as busy
     */
    public function setAsBusy(): void
    {
        $this->update(['availability' => 'busy']);
    }

    /**
     * Set waiter as available
     */
    public function setAsAvailable(): void
    {
        if ($this->current_orders < $this->maximum_orders) {
            $this->update(['availability' => 'available']);
        }
    }

    /**
     * Set waiter on break
     */
    public function setOnBreak(): void
    {
        $this->update(['availability' => 'break']);
    }

    /**
     * Set waiter offline
     */
    public function setOffline(): void
    {
        $this->update(['availability' => 'offline']);
    }

    /**
     * Get today's floor assignment for specific shift
     */
    public function getTodayFloorAssignment($shiftId)
    {
        return $this->floorAssignments()
            ->where('shift_id', $shiftId)
            ->where('assignment_date', now()->toDateString())
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get total deliveries today
     */
    public function getTodayDeliveries()
    {
        return $this->deliveryTasks()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();
    }

    /**
     * Get pending deliveries
     */
    public function getPendingDeliveries()
    {
        return $this->deliveryTasks()
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
            ->count();
    }

    /**
     * Get average delivery time in minutes
     */
    public function getAverageDeliveryTime(): float
    {
        return $this->deliveryTasks()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->get()
            ->average(function ($task) {
                if ($task->assigned_at && $task->delivered_at) {
                    return $task->assigned_at->diffInMinutes($task->delivered_at);
                }
                return 0;
            }) ?? 0;
    }

    /**
     * Deactivate waiter
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => 'inactive',
            'availability' => 'offline',
        ]);
    }

    /**
     * Reactivate waiter
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'availability' => 'offline',
        ]);
    }

    /**
     * Suspend waiter
     */
    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
            'availability' => 'offline',
        ]);
    }

    /**
     * Get pending assignments
     */
    public function pendingAssignments()
    {
        return $this->assignments()->where('status', 'pending');
    }

    /**
     * Get active assignments (accepted, on delivery)
     */
    public function activeAssignments()
    {
        return $this->assignments()->whereIn('status', ['accepted', 'on_delivery']);
    }

    /**
     * Get waiter with full data
     */
    public function scopeWithStats($query)
    {
        return $query->with([
            'user',
            'assignments' => function ($q) {
                $q->where('status', 'pending');
            },
            'performanceMetrics' => function ($q) {
                $q->latest('metric_date')->limit(1);
            }
        ]);
    }
}
