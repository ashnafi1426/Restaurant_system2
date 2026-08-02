<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WalkInPayment Model
 * Tracks payment transactions for walk-in customer orders
 * Currently integrated with Chapa payment gateway
 */
class WalkInPayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'walk_in_payments';

    protected $fillable = [
        'restaurant_order_id',
        'provider',
        'payment_method',
        'amount',
        'currency',
        'transaction_reference',
        'payment_status',
        'paid_at',
        'verified_at',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id');
    }

    /**
     * Mark payment as paid (after Chapa redirect)
     */
    public function markPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as verified (after webhook confirmation)
     */
    public function markVerified(): void
    {
        $this->update([
            'payment_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markFailed(): void
    {
        $this->update(['payment_status' => 'failed']);
    }

    /**
     * Mark payment as refunded
     */
    public function markRefunded(): void
    {
        $this->update(['payment_status' => 'refunded']);
    }

    /**
     * Check if payment is verified
     */
    public function isVerified(): bool
    {
        return $this->payment_status === 'verified';
    }

    /**
     * Store raw Chapa response for debugging
     */
    public function storeRawResponse(array $response): void
    {
        $this->update(['raw_response' => json_encode($response)]);
    }

    /**
     * Scope: Get payments by status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope: Get today's payments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Get verified payments
     */
    public function scopeVerified($query)
    {
        return $query->where('payment_status', 'verified');
    }

    /**
     * Scope: Get failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }
}
