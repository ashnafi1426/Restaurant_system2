<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manager extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'employee_code',
        'department',
        'bio',
        'profile_photo',
        'hire_date',
        'status',
        'permissions',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'permissions' => 'array',
    ];

    /**
     * Get the user associated with this manager
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    /**
     * Get all audit logs for this manager
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ManagerAuditLog::class, 'manager_id', 'id');
    }

    /**
     * Get all notifications for this manager
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(ManagerNotification::class, 'manager_id', 'id');
    }

    /**
     * Check if manager is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if manager is on leave
     */
    public function isOnLeave(): bool
    {
        return $this->status === 'on_leave';
    }

    /**
     * Scope to get active managers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
