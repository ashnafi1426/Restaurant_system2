<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

class Order extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    protected $table = 'orders';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'hotel_id',
        'order_number',
        'reservation_id',
        'guest_id',
        'room_id',
        'table_id',
        'order_type',
        'order_time',
        'status',
        'notes',
        'payment_type',
        'subtotal',
        'tax',
        'discount',
        'total',
        'served_at',
        'cancelled_at',
    ];

    protected $casts = [
        'order_time' => 'datetime',
        'served_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_SERVED = 'served';
    public const STATUS_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Order Type Constants
    |--------------------------------------------------------------------------
    */

    public const TYPE_ROOM_SERVICE = 'room_service';
    public const TYPE_WALK_IN = 'walk_in';

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        return sprintf('%s-%04d', $prefix, $count);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Order has many reviews
     */
    public function reviews()
    {
        return $this->hasMany(MenuItemReview::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPreparing(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isServed(): bool
    {
        return $this->status === self::STATUS_SERVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_SERVED, 'completed']);
    }

    /*
    |--------------------------------------------------------------------------
    | Order Type Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isRoomService(): bool
    {
        return $this->order_type === self::TYPE_ROOM_SERVICE;
    }

    public function isWalkIn(): bool
    {
        return $this->order_type === self::TYPE_WALK_IN;
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Calculations
    |--------------------------------------------------------------------------
    */

    public function getGrandTotalAttribute()
    {
        return $this->orderItems->sum('total');
    }

    public function getTotalItemsAttribute()
    {
        return $this->orderItems->sum('quantity');
    }

    /**
     * Get reviewable items from this order
     */
    public function getReviewableItemsAttribute()
    {
        if (!$this->isCompleted()) {
            return collect([]);
        }
        
        return $this->orderItems()
            ->with('menuItem')
            ->whereDoesntHave('menuItem.reviews', function ($query) {
                $query->where('guest_id', $this->guest_id)
                      ->where('order_id', $this->id);
            })
            ->get()
            ->pluck('menuItem');
    }
}