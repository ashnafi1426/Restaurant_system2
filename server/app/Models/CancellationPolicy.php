<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

class CancellationPolicy extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'cancellation_policies';

    public $incrementing = false;

    protected $keyType = 'string';

    public const TYPE_FLEXIBLE = 'flexible';
    public const TYPE_MODERATE = 'moderate';
    public const TYPE_STRICT = 'strict';
    public const TYPE_NON_REFUNDABLE = 'non_refundable';

    protected $fillable = [
        'hotel_id',
        'name',
        'type',
        'description',
        'cancellation_deadline_days',
        'refund_percentage',
        'minimum_stay_nights',
        'applies_to',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'cancellation_deadline_days' => 'integer',
        'refund_percentage' => 'decimal:2',
        'minimum_stay_nights' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'cancellation_policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFlexible($query)
    {
        return $query->where('type', self::TYPE_FLEXIBLE);
    }

    public function scopeModerate($query)
    {
        return $query->where('type', self::TYPE_MODERATE);
    }

    public function scopeStrict($query)
    {
        return $query->where('type', self::TYPE_STRICT);
    }

    public function scopeNonRefundable($query)
    {
        return $query->where('type', self::TYPE_NON_REFUNDABLE);
    }

    public function calculateRefund(
        float $totalAmount,
        \DateTime $checkInDate,
        \DateTime $cancellationDate
    ): array {
        $daysUntilCheckIn = $checkInDate->diff($cancellationDate)->days;

        if ($this->type === self::TYPE_NON_REFUNDABLE) {
            return [
                'refund_amount' => 0,
                'refund_percentage' => 0,
                'is_refundable' => false,
                'reason' => 'Non-refundable policy',
            ];
        }

        if ($daysUntilCheckIn < $this->cancellation_deadline_days) {
            return [
                'refund_amount' => 0,
                'refund_percentage' => 0,
                'is_refundable' => false,
                'reason' => "Cancelled within {$this->cancellation_deadline_days} days of check-in",
                'days_remaining' => $daysUntilCheckIn,
                'cancellation_deadline' => $this->cancellation_deadline_days,
            ];
        }

        $refundPercentage = $this->getRefundPercentage($daysUntilCheckIn);
        $refundAmount = ($totalAmount * $refundPercentage) / 100;

        return [
            'refund_amount' => round($refundAmount, 2),
            'refund_percentage' => $refundPercentage,
            'is_refundable' => true,
            'reason' => 'Refund eligible - ' . $this->name,
            'days_until_checkin' => $daysUntilCheckIn,
        ];
    }

    private function getRefundPercentage(int $daysUntilCheckIn): float
    {
        return match ($this->type) {
            self::TYPE_FLEXIBLE => 100,
            self::TYPE_MODERATE => $daysUntilCheckIn >= 14 ? 100 : 50,
            self::TYPE_STRICT => $daysUntilCheckIn >= 30 ? 100 : ($daysUntilCheckIn >= 7 ? 50 : 0),
            self::TYPE_NON_REFUNDABLE => 0,
            default => (float) $this->refund_percentage,
        };
    }

    public function canCancel(Reservation $reservation): array
    {
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return [
                'can_cancel' => false,
                'reason' => "Cannot cancel reservation with status: {$reservation->status}",
            ];
        }

        $refund = $this->calculateRefund(
            $reservation->total_amount ?? 0,
            $reservation->check_in_date,
            now()
        );

        return [
            'can_cancel' => true,
            'reason' => 'Cancellation eligible',
            'refund' => $refund,
        ];
    }

    public static function getDefaultPolicy(string $hotelId): ?self
    {
        return self::where('hotel_id', $hotelId)
            ->where('type', self::TYPE_MODERATE)
            ->active()
            ->first();
    }

    public static function createStandardPolicies(string $hotelId, string $createdBy): void
    {
        $policies = [
            [
                'name' => 'Flexible Cancellation',
                'type' => self::TYPE_FLEXIBLE,
                'description' => 'Full refund up to 24 hours before check-in',
                'cancellation_deadline_days' => 1,
                'refund_percentage' => 100,
            ],
            [
                'name' => 'Moderate Cancellation',
                'type' => self::TYPE_MODERATE,
                'description' => 'Full refund if cancelled 14+ days before check-in, 50% refund within 14 days',
                'cancellation_deadline_days' => 14,
                'refund_percentage' => 50,
            ],
            [
                'name' => 'Strict Cancellation',
                'type' => self::TYPE_STRICT,
                'description' => 'Full refund if cancelled 30+ days before, 50% if cancelled 7+ days before',
                'cancellation_deadline_days' => 7,
                'refund_percentage' => 50,
            ],
            [
                'name' => 'Non-Refundable',
                'type' => self::TYPE_NON_REFUNDABLE,
                'description' => 'No refund allowed',
                'cancellation_deadline_days' => 0,
                'refund_percentage' => 0,
            ],
        ];

        foreach ($policies as $policy) {
            self::create([
                'hotel_id' => $hotelId,
                'name' => $policy['name'],
                'type' => $policy['type'],
                'description' => $policy['description'],
                'cancellation_deadline_days' => $policy['cancellation_deadline_days'],
                'refund_percentage' => $policy['refund_percentage'],
                'applies_to' => 'all',
                'is_active' => true,
                'created_by' => $createdBy,
            ]);
        }
    }
}

