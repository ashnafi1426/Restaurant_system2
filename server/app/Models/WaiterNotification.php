<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class WaiterNotification extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'waiter_notifications';

    protected $fillable = [
        'hotel_id',
        'waiter_id',
        'delivery_task_id',
        'order_id',
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

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(Waiter::class, 'waiter_id', 'id');
    }

    public function deliveryTask(): BelongsTo
    {
        return $this->belongsTo(DeliveryTask::class, 'delivery_task_id', 'id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function markAsRead()
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread()
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }
}
