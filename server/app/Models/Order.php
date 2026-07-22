<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'orders';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_number',
        'reservation_id',
        'guest_id',
        'room_id',
        'order_time',
        'status',
        'source',
        'notes',
        'special_requests',
        'payment_type',
        'subtotal',
        'tax',
        'discount',
        'total',
        'served_at',
        'cancelled_at',
        'user_id',
        'chef_id',
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

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function chef()
    {
        return $this->belongsTo(User::class, 'chef_id');
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
}