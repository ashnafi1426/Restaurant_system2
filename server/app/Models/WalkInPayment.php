<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id');
    }

    public function markPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markVerified(): void
    {
        $this->update([
            'payment_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['payment_status' => 'failed']);
    }

    public function markRefunded(): void
    {
        $this->update(['payment_status' => 'refunded']);
    }

    public function isVerified(): bool
    {
        return $this->payment_status === 'verified';
    }

    public function storeRawResponse(array $response): void
    {
        $this->update(['raw_response' => json_encode($response)]);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeVerified($query)
    {
        return $query->where('payment_status', 'verified');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }
}

