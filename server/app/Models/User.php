<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'role',
        'role_id',
        'is_active',
        'must_change_password',
        'is_platform_admin',
        'activation_token',
        'activation_token_expires_at',
        'activation_status',
        'email_verified_at'
    ];
    protected $hidden = [
        'password_hash',
        'remember_token'
    ];
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'is_platform_admin' => 'boolean',
            'activation_token_expires_at' => 'datetime',
            'email_verified_at' => 'datetime'
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    public function isReceptionist()
    {
        return $this->role === 'receptionist';
    }
    public function isCashier()
    {
        return $this->role === 'cashier';
    }
    public function isManager()
    {
        return $this->role === 'manager';
    }
    public function isChef()
    {
        return $this->role === 'chef';
    }

    public function isActivated(): bool
    {
        return in_array($this->activation_status, ['activated', 'active']) || !empty($this->password_hash);
    }

    public function needsActivation(): bool
    {
        if (!empty($this->password_hash) && $this->is_active) {
            return false;
        }

        return in_array($this->activation_status, ['pending', 'expired']);
    }

    public function hasValidActivationToken(): bool
    {
        return $this->activation_token 
            && $this->activation_token_expires_at 
            && $this->activation_token_expires_at->isFuture();
    }
    public function managerNotifications()
    {
        return $this->hasMany(ManagerNotification::class, 'manager_id');
    }

    public function managerActivityLogs()
    {
        return $this->hasMany(ManagerActivityLog::class, 'manager_id');
    }

    public function managerDashboardSetting()
    {
        return $this->hasOne(ManagerDashboardSetting::class, 'manager_id');
    }

    public function managerAnnouncements()
    {
        return $this->hasMany(ManagerAnnouncement::class, 'manager_id');
    }

    public function managerReports()
    {
        return $this->hasMany(ManagerReport::class, 'manager_id');
    }

    public function assignments()
    {
        return $this->hasMany(WaiterAssignment::class, 'waiter_id');
    }

    public function waiter()
    {
        return $this->hasOne(Waiter::class, 'user_id', 'id');
    }

    public function administrator()
    {
        return $this->hasOne(Administrator::class, 'id', 'id');
    }

    public function manager()
    {
        return $this->hasOne(Manager::class, 'id', 'id');
    }

    public function receptionist()
    {
        return $this->hasOne(Receptionist::class, 'id', 'id');
    }

    public function cashier()
    {
        return $this->hasOne(Cashier::class, 'id', 'id');
    }

    public function chef()
    {
        return $this->hasOne(Chef::class, 'id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function temporaryRoleAssignments()
    {
        return $this->hasMany(TemporaryRoleAssignment::class, 'user_id');
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id');
    }

    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
                    ->withPivot('granted_by', 'starts_at', 'expires_at')
                    ->withTimestamps();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasPermission($this, $permissionSlug);
    }

    public function hasAnyPermission(array $permissionSlugs): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasAnyPermission($this, $permissionSlugs);
    }

    public function hasAllPermissions(array $permissionSlugs): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasAllPermissions($this, $permissionSlugs);
    }

    public function hasRole(string $roleSlug): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasRole($this, $roleSlug);
    }

    public function getEffectivePermissions(): array
    {
        return app(\App\Services\AuthorizationService::class)->getEffectivePermissions($this);
    }

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_users', 'user_id', 'hotel_id')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function hotelMemberships()
    {
        return $this->hasMany(HotelUser::class, 'user_id');
    }

    public function belongsToHotel(string $hotelId): bool
    {
        return $this->hotelMemberships()
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();
    }

    public function getHotelRole(string $hotelId): ?string
    {
        $roleFromUserRoles = \App\Models\Role::withoutTenant()
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->whereHas('users', function ($q) use ($hotelId) {
                $q->where('user_roles.user_id', $this->id)
                  ->where('user_roles.hotel_id', $hotelId);
            })
            ->first();

        if ($roleFromUserRoles) {
            return strtolower($roleFromUserRoles->slug);
        }

        $membership = $this->hotelMemberships()
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->first();

        if ($membership) {
            if ($membership->role_id) {
                $roleModel = \App\Models\Role::withoutTenant()->find($membership->role_id);
                if ($roleModel) {
                    return strtolower($roleModel->slug);
                }
            }
            if (!empty($membership->role)) {
                return strtolower($membership->role);
            }
        }

        return !empty($this->role) ? strtolower($this->role) : null;
    }
}
