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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ManagerAuditLog::class, 'manager_id', 'id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ManagerNotification::class, 'manager_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnLeave(): bool
    {
        return $this->status === 'on_leave';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

