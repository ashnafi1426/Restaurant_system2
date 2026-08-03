<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payments';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * --------------------------------------------------------------------------
     * Mass Assignable
     * --------------------------------------------------------------------------
     */
    protected $fillable = [
        'tx_ref',
        'chapa_transaction_id',

        'amount',
        'currency',

        'first_name',
        'last_name',
        'email',
        'phone',

        'payment_provider',
        'payment_method',

        'status',

        'checkout_url',
        'callback_url',
        'return_url',

        'reservation_id',
        'order_id',
        'guest_id',

        'paid_at',
        'verified_at',

        'raw_response',
        'metadata',
    ];

    /**
     * --------------------------------------------------------------------------
     * Attribute Casting
     * --------------------------------------------------------------------------
     */
    protected $casts = [
        'amount' => 'decimal:2',

        'paid_at' => 'datetime',
        'verified_at' => 'datetime',

        'raw_response' => 'array',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Payment Provider
    |--------------------------------------------------------------------------
    */

    public const PROVIDER_CHAPA = 'chapa';

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    public const CURRENCY_ETB = 'ETB';

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING = 'pending';

    public const STATUS_INITIALIZED = 'initialized';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInitialized(): bool
    {
        return $this->status === self::STATUS_INITIALIZED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /*
    |--------------------------------------------------------------------------
    | Status Update Methods
    |--------------------------------------------------------------------------
    */

    public function markAsInitialized(string $checkoutUrl): void
    {
        $this->update([
            'status' => self::STATUS_INITIALIZED,
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    public function markAsPaid(?string $transactionId = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'chapa_transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }

    public function markAsVerified(array $response = []): void
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_at' => now(),
            'raw_response' => $response,
        ]);
    }

    public function markAsFailed(array $response = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'raw_response' => $response,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    public function markAsRefunded(): void
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getCustomerNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Payment belongs to Reservation
     * 
     * A payment may be linked to a hotel reservation
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    /**
     * Payment belongs to Order
     * 
     * A payment may be linked to a restaurant order (QR food ordering)
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Payment belongs to Guest
     * 
     * A payment is associated with a guest
     */
    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id');
    }
}