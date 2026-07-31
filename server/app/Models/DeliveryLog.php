<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryLog extends Model
{
    use HasFactory;

    protected $table = 'delivery_logs';

    protected $fillable = [
        'waiter_id',
        'order_id',
        'room_id',
        'action',
        'description',
        'location',
    ];

    protected $casts = [
        'location' => 'json',
    ];

    /**
     * Get the waiter
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the room
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Log an action
     */
    public static function logAction(int $waiterId, int $orderId, string $action, string $description = null, int $roomId = null, array $location = null): self
    {
        return self::create([
            'waiter_id' => $waiterId,
            'order_id' => $orderId,
            'room_id' => $roomId,
            'action' => $action,
            'description' => $description,
            'location' => $location,
        ]);
    }

    /**
     * Scope: Get logs for waiter
     */
    public function scopeForWaiter($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId);
    }

    /**
     * Scope: Get logs for order
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope: Get today's logs
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Get recent logs
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get logs by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}
