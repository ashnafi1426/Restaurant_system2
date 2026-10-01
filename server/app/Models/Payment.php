<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

class Payment extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'payments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'hotel_id',
        'tx_ref',
        'transaction_reference',
        'invoice_id',
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
        'payment_status',
        'checkout_url',
        'callback_url',
        'return_url',
        'reservation_id',
        'order_id',
        'guest_id',
        'paid_at',
        'verified_at',
        'refunded_at',
        'refund_reason',
        'refund_amount',
        'raw_response',
        'metadata',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($payment) {
            if (empty($payment->transaction_reference) && !empty($payment->tx_ref)) {
                $payment->transaction_reference = $payment->tx_ref;
            }
            if (empty($payment->tx_ref) && !empty($payment->transaction_reference)) {
                $payment->tx_ref = $payment->transaction_reference;
            }
            if (empty($payment->guest_id) && !empty($payment->email)) {
                try {
                    $hotelId = $payment->hotel_id ?? app(\App\Services\TenantContext::class)->getHotelId();
                    $guest = \App\Models\Guest::withoutGlobalScopes()
                        ->where('email', $payment->email)
                        ->when($hotelId, fn($q) => $q->where(function ($sub) use ($hotelId) {
                            $sub->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
                        }))
                        ->first();
                    if (!$guest) {
                        $guest = new \App\Models\Guest();
                        $guest->id = (string) \Illuminate\Support\Str::uuid();
                        $guest->hotel_id = $hotelId;
                        $guest->first_name = $payment->first_name ?: 'Walk-in';
                        $guest->last_name = $payment->last_name ?: 'Guest';
                        $guest->email = $payment->email;
                        $guest->phone = $payment->phone ?: 'N/A';
                        $guest->save();
                    }
                    if ($guest) {
                        $payment->guest_id = $guest->id;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[PAYMENT] Auto-resolve guest in booted failed: ' . $e->getMessage());
                }
            }
        });
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'refunded_at' => 'datetime',
        'raw_response' => 'array',
        'metadata' => 'array',
    ];

    public const PROVIDER_CHAPA = 'chapa';

    public const CURRENCY_ETB = 'ETB';

    public const STATUS_PENDING = 'pending';

    public const STATUS_INITIALIZED = 'initialized';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

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

    public function updatePaymentStatus(
        string $status,
        array $details = [],
        ?string $transactionId = null,
        ?string $paymentMethod = null
    ): void {
        $updateData = [
            'status' => $status,
            'payment_status' => $status,
            'raw_response' => $details,
        ];

        if ($transactionId) {
            $updateData['chapa_transaction_id'] = $transactionId;
        }

        if ($paymentMethod) {
            $updateData['payment_method'] = $paymentMethod;
        }

        if ($status === 'completed' || $status === 'verified') {
            $updateData['paid_at'] = now();
            $updateData['verified_at'] = now();
        }

        $this->update($updateData);
    }

    public function getCustomerNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id');
    }
}