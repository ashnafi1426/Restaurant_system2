<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToTenant;

class Waiter extends Model
{
    use HasFactory, BelongsToTenant;
    protected $table = 'waiters';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'hotel_id',
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
        'last_assigned_at',
        'profile_photo',
        'bio',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'current_orders' => 'integer',
        'maximum_orders' => 'integer',
        'last_assigned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function floorAssignments(): HasMany
    {
        return $this->hasMany(WaiterFloorAssignment::class);
    }

    public function deliveryTasks(): HasMany
    {
        return $this->hasMany(DeliveryTask::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(WaiterNotification::class, 'waiter_id', 'id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WaiterAssignment::class, 'waiter_id', 'id');
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(WaiterPerformance::class, 'waiter_id', 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability', 'available');
    }

    public function scopeWithCapacity($query)
    {
        return $query->whereRaw('current_orders < maximum_orders');
    }

    public function scopeFullTime($query)
    {
        return $query->where('employment_type', 'full_time');
    }

    public function scopeOnBreak($query)
    {
        return $query->where('status', 'on_break');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' &&
               $this->availability === 'available' &&
               $this->current_orders < $this->maximum_orders;
    }

    public function canTakeOrders(): bool
    {
        return $this->current_orders < $this->maximum_orders;
    }

    public function isOnBreak(): bool
    {
        return $this->availability === 'break';
    }

    public function isOffline(): bool
    {
        return $this->availability === 'offline';
    }

    public function incrementOrders(): bool
    {
        $updated = \Illuminate\Support\Facades\DB::table($this->getTable())
            ->where('id', $this->id)
            ->where(function ($q) {
                $q->where('maximum_orders', 0)
                  ->orWhereRaw('current_orders < maximum_orders');
            })
            ->increment('current_orders');

        if ($updated) {
            $this->current_orders++;
            return true;
        }
        return false;
    }

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

    public function setAsBusy(): void
    {
        $this->update(['availability' => 'busy']);
    }

    public function setAsAvailable(): void
    {
        if ($this->current_orders < $this->maximum_orders) {
            $this->update(['availability' => 'available']);
        }
    }

    public function setOnBreak(): void
    {
        $this->update(['availability' => 'break']);
    }

    public function setOffline(): void
    {
        $this->update(['availability' => 'offline']);
    }

    public function getTodayFloorAssignment($shiftId)
    {
        return $this->floorAssignments()
            ->where('shift_id', $shiftId)
            ->where('assignment_date', now()->toDateString())
            ->where('status', 'active')
            ->first();
    }

    public function getTodayDeliveries()
    {
        return $this->deliveryTasks()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();
    }

    public function getPendingDeliveries()
    {
        return $this->deliveryTasks()
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
            ->count();
    }

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

    public function deactivate(): void
    {
        $this->update([
            'status' => 'inactive',
            'availability' => 'offline',
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'availability' => 'offline',
        ]);
    }

    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
            'availability' => 'offline',
        ]);
    }

    public function pendingAssignments()
    {
        return $this->assignments()->where('status', 'pending');
    }

    public function activeAssignments()
    {
        return $this->assignments()->whereIn('status', ['accepted', 'on_delivery']);
    }

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
