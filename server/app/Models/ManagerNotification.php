<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerNotification extends Model
{
    use HasFactory;

    protected $table = 'manager_notifications';

    protected $fillable = [
        'manager_id',
        'delivery_task_id',
        'type',
        'title',
        'message',
        'priority',
        'icon',
        'color',
        'related_id',
        'related_type',
        'read',
        'is_read',
        'read_at',
        'expires_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the manager for this notification
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the delivery task for this notification
     */
    public function deliveryTask(): BelongsTo
    {
        return $this->belongsTo(DeliveryTask::class, 'delivery_task_id', 'id');
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope: Get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Get urgent notifications
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent')
            ->orWhere('priority', 'high');
    }

    /**
     * Scope: Get active notifications (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Get notifications by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get recent notifications
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
