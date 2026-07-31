<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeliveryTask Model
 * Tracks individual delivery assignments and their status throughout the delivery lifecycle
 */
class DeliveryTask extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'delivery_tasks';
    protected $fillable = [
        'order_id',
        'reservation_id',
        'room_id',
        'floor_id',
        'waiter_id',
        'assigned_by',
        'assignment_type',
        'status',
        'assigned_at',
        'accepted_at',
        'picked_up_at',
        'on_delivery_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'remarks',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'on_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the floor
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(HotelFloor::class);
    }

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the waiter
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class);
    }

    /**
     * Get the manager who assigned
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get tasks for a waiter today
     */
    public function scopeForWaiterToday($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId)
            ->whereDate('assigned_at', today());
    }

    /**
     * Scope to get pending tasks (not yet assigned)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'waiting_assignment');
    }

    /**
     * Scope to get assigned tasks
     */
    public function scopeAssigned($query)
    {
        return $query->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery']);
    }

    /**
     * Scope to get completed tasks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope to get cancelled tasks
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Accept delivery task
     * Idempotent - can be called multiple times safely
     * Accepts both 'assigned' (legacy) and auto-assigned (already accepted) tasks
     */
    public function accept(Waiter $waiter): void
    {
        // Already accepted - allow re-entry (idempotent)
        if ($this->status === 'accepted') {
            \Log::info('Task already accepted - idempotent call allowed', ['id' => $this->id]);
            return;
        }

        if (!in_array($this->status, ['assigned', 'waiting_assignment'])) {
            throw new \Exception("Cannot accept delivery task in '{$this->status}' state. Expected 'assigned', 'waiting_assignment', or 'accepted'.");
        }

        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
        
        // Refresh to sync model with DB
        $this->refresh();
        
        $waiter->incrementOrders();
    }

    /**
     * Mark as picked up
     * Idempotent - can be called multiple times safely (e.g., on retry)
     */
    public function markPickedUp(): void
    {
        // Already picked up - allow re-entry (idempotent)
        if ($this->status === 'picked_up') {
            \Log::info('Task already picked up - idempotent call allowed', ['id' => $this->id]);
            return;
        }

        if ($this->status !== 'accepted' && $this->status !== 'assigned') {
            throw new \Exception("Cannot pickup delivery task in '{$this->status}' state. Expected 'assigned', 'accepted', or 'picked_up'.");
        }

        $this->update([
            'status' => 'picked_up',
            'picked_up_at' => now(),
        ]);
        
        // Refresh to sync model with DB
        $this->refresh();
    }

    /**
     * Mark as on delivery
     * Idempotent - can be called multiple times safely (e.g., on retry)
     * Accepts both 'assigned' (legacy), 'accepted' (new), and 'picked_up'
     */
    public function markOnDelivery(): void
    {
        // Already on delivery - allow re-entry (idempotent)
        if ($this->status === 'on_delivery') {
            \Log::info('Task already on delivery - idempotent call allowed', ['id' => $this->id]);
            return;
        }

        if (!in_array($this->status, ['assigned', 'accepted', 'picked_up'])) {
            throw new \Exception("Cannot start delivery in '{$this->status}' state. Expected 'assigned', 'accepted', 'picked_up', or 'on_delivery'.");
        }

        $this->update([
            'status' => 'on_delivery',
            'on_delivery_at' => now(),
        ]);
        
        // Refresh to sync model with DB
        $this->refresh();
    }

    /**
     * Mark as delivered
     * Idempotent - can be called multiple times safely (e.g., on retry)
     * Accepts both 'assigned' (legacy), 'picked_up', and 'on_delivery'
     */
    public function markDelivered(string $remarks = null): void
    {
        // Already delivered - allow re-entry (idempotent)
        if ($this->status === 'delivered') {
            \Log::info('Task already delivered - idempotent call allowed', ['id' => $this->id]);
            return;
        }

        if (!in_array($this->status, ['assigned', 'accepted', 'picked_up', 'on_delivery'])) {
            throw new \Exception("Cannot complete delivery in '{$this->status}' state. Expected 'assigned', 'accepted', 'picked_up', 'on_delivery', or 'delivered'.");
        }

        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'remarks' => $remarks,
        ]);
        
        // Refresh to sync model with DB
        $this->refresh();

        if ($this->waiter) {
            $this->waiter->decrementOrders();
        }
    }

    /**
     * Cancel delivery
     */
    public function cancel(string $reason = null): void
    {
        if (in_array($this->status, ['delivered', 'cancelled'])) {
            throw new \Exception("Cannot cancel delivery that is already '{$this->status}'.");
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        if ($this->waiter && in_array($this->status, ['assigned', 'accepted', 'picked_up', 'on_delivery'])) {
            $this->waiter->decrementOrders();
        }
    }

    /**
     * Reassign to another waiter
     */
    public function reassign(Waiter $newWaiter, $assignedBy, string $reason = null): void
    {
        // Decrement old waiter's orders
        if ($this->waiter) {
            $this->waiter->decrementOrders();
        }

        // Update task
        $this->update([
            'waiter_id' => $newWaiter->id,
            'assigned_by' => $assignedBy,
            'assignment_type' => 'manual',
            'remarks' => $reason ? "{$reason} - Reassigned" : 'Reassigned',
        ]);

        // Increment new waiter's orders
        $newWaiter->incrementOrders();
    }

    /**
     * Get delivery duration in minutes
     */
    public function getDeliveryDurationMinutes(): ?int
    {
        if ($this->assigned_at && $this->delivered_at) {
            return $this->assigned_at->diffInMinutes($this->delivered_at);
        }
        return null;
    }

    /**
     * Check if delivery is late (> 30 minutes)
     */
    public function isLate(): bool
    {
        $duration = $this->getDeliveryDurationMinutes();
        return $duration && $duration > 30;
    }

    /**
     * Get delivery status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'waiting_assignment' => 'Waiting for Waiter',
            'assigned' => 'Assigned',
            'accepted' => 'Accepted by Waiter',
            'picked_up' => 'Picked from Kitchen',
            'on_delivery' => 'On the Way',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }
}
