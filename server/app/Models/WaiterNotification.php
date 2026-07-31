<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterNotification extends Model
{
    use HasUuids;

    protected $table = 'waiter_notifications';

    protected $fillable = [
        'waiter_id',
        'delivery_task_id',
        'type',
        'title',
        'message',
        'read',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'json',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to Waiter
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class, 'waiter_id', 'id');
    }

    /**
     * Relationship to DeliveryTask
     */
    public function deliveryTask(): BelongsTo
    {
        return $this->belongsTo(DeliveryTask::class, 'delivery_task_id', 'id');
    }

    /**
     * Scope: Unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark as unread
     */
    public function markAsUnread()
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }
}
