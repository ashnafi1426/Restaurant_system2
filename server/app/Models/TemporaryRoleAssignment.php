<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryRoleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_id',
        'starts_at',
        'expires_at',
        'assigned_by',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isValidNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now->greaterThanOrEqualTo($this->starts_at) && $now->lessThanOrEqualTo($this->expires_at);
    }
}
