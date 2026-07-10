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

    /**
     * The table associated with the model.
     */
    protected $table = 'restaurant_charges';

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Reservation that owns this charge.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Restaurant order that generated this charge.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Only paid charges.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Only cancelled charges.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Determine if the charge is unpaid.
     */
    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    /**
     * Determine if the charge is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Determine if the charge is cancelled.
     */
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