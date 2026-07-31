<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterAssignment extends Model
{
    use HasFactory;

    protected $table = 'waiter_assignments';

    protected $fillable = [
        'waiter_id',
        'order_id',
        'assigned_by',
        'assigned_at',
        'accepted_at',
        'rejected_at',
        'picked_up_at',
        'delivered_at',
        'failed_at',
        'status',
        'rejection_reason',
        'failure_reason',
        'remarks',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get who assigned this
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Accept assignment
     */
    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Reject assignment
     */
    public function reject(?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark as picked up
     */
    public function pickup(): void
    {
        $this->update([
            'status' => 'picked_up',
            'picked_up_at' => now(),
        ]);
    }

    /**
     * Start delivery
     */
    public function startDelivery(): void
    {
        $this->update([
            'status' => 'on_delivery',
        ]);
    }

    /**
     * Mark as delivered
     */
    public function deliver(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get delivery time in minutes
     */
    public function getDeliveryTimeMinutes(): ?int
    {
        if (!$this->assigned_at || !$this->delivered_at) {
            return null;
        }
        return $this->assigned_at->diffInMinutes($this->delivered_at);
    }

    /**
     * Scope: Get pending assignments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get accepted assignments
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: Get on delivery assignments
     */
    public function scopeOnDelivery($query)
    {
        return $query->where('status', 'on_delivery');
    }

    /**
     * Scope: Get completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope: Get failed assignments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get assignments for waiter
     */
    public function scopeForWaiter($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId);
    }

    /**
     * Scope: Get today's assignments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('assigned_at', today());
    }

    /**
     * Scope: Get active assignments (not completed/failed/rejected)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['delivered', 'failed', 'rejected', 'cancelled']);
    }
}
