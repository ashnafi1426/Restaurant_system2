<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerAuditLog extends Model
{
    use HasFactory;

    protected $table = 'manager_audit_logs';

    protected $fillable = [
        'manager_id',
        'action',
        'resource_type',
        'resource_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopeForManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeForResource($query, $resourceType, $resourceId = null)
    {
        $query->where('resource_type', $resourceType);
        if ($resourceId) {
            $query->where('resource_id', $resourceId);
        }
        return $query;
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
