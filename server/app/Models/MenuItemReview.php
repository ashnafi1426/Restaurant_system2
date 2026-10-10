<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToTenant;

class MenuItemReview extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'menu_item_reviews';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'hotel_id',
        'guest_id',
        'order_id',
        'menu_item_id',
        'rating',
        'review_text',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function response()
    {
        return $this->hasOne(ReviewResponse::class, 'review_id');
    }

    public function votes()
    {
        return $this->hasMany(ReviewHelpfulnessVote::class, 'review_id');
    }

    public function notifications()
    {
        return $this->hasMany(ReviewNotification::class, 'review_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForMenuItem($query, string $menuItemId)
    {
        return $query->where('menu_item_id', $menuItemId);
    }

    public function scopeByGuest($query, string $guestId)
    {
        return $query->where('guest_id', $guestId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByHelpfulness($query)
    {
        return $query->orderByRaw('helpful_count / (helpful_count + not_helpful_count + 1) DESC');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeModifiedByGuest(): bool
    {
        return !$this->isRejected();
    }

    public function getAnonymizedGuestNameAttribute(): string
    {
        if (!$this->guest) {
            return 'Anonymous Guest';
        }

        $lastName = $this->guest->last_name ? strtoupper(substr($this->guest->last_name, 0, 1)) . '.' : '';
        return trim($this->guest->first_name . ' ' . $lastName);
    }

    public function getHelpfulnessRatioAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        return $total > 0 ? round($this->helpful_count / $total, 2) : 0;
    }

    public function getPublicDisplayDataAttribute(): array
    {
        return [
            'id' => $this->id,
            'guest_name' => $this->anonymized_guest_name,
            'rating' => $this->rating,
            'review_text' => $this->review_text,
            'helpful_count' => $this->helpful_count,
            'not_helpful_count' => $this->not_helpful_count,
            'helpfulness_ratio' => $this->helpfulness_ratio,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : now()->toIso8601String(),
            'response' => $this->response ? [
                'text' => $this->response->response_text,
                'responder_role' => $this->response->responder->role ?? 'Manager',
                'created_at' => $this->response->created_at->toIso8601String(),
            ] : null,
        ];
    }
}

