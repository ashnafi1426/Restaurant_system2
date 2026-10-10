<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestaurantCharge extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'restaurant_charges';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'reservation_id',
        'order_id',
        'amount',
        'description',
        'status',
        'paid_at',
        'payment_reference',
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function markAsPaid(?string $reference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $reference,
        ]);
    }
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}

