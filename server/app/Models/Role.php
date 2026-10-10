<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToTenant;

class Role extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'description',
        'is_system',
        'is_active',
    ];

    protected $appends = [
        'display_name',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function getDisplayNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
                    ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function temporaryAssignments(): HasMany
    {
        return $this->hasMany(TemporaryRoleAssignment::class, 'role_id');
    }
}

