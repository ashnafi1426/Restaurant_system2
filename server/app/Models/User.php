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
            'activation_token_expires_at' => 'datetime',
            'email_verified_at' => 'datetime'
        ];
    }

    /**
     * Get the password for authentication.
     * Since our column is password_hash, we need to tell Laravel to use it.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
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

    /**
     * Check if user account is activated.
     */
    public function isActivated(): bool
    {
        return $this->activation_status === 'activated';
    }

    /**
     * Check if user needs activation.
     */
    public function needsActivation(): bool
    {
        return in_array($this->activation_status, ['pending', 'expired']);
    }

    /**
     * Check if activation token is valid (not expired).
     */
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

    /**
     * Get all waiter assignments for this user
     * (only if user is a waiter)
     */
    public function assignments()
    {
        return $this->hasMany(WaiterAssignment::class, 'waiter_id');
    }

    /**
     * Get the waiter profile linked to this user.
     */
    public function waiter()
    {
        return $this->hasOne(Waiter::class, 'user_id', 'id');
    }

    /**
     * Get the administrator profile linked to this user.
     */
    public function administrator()
    {
        return $this->hasOne(Administrator::class, 'id', 'id');
    }

    /**
     * Get the manager profile linked to this user.
     */
    public function manager()
    {
        return $this->hasOne(Manager::class, 'id', 'id');
    }

    /**
     * Get the receptionist profile linked to this user.
     */
    public function receptionist()
    {
        return $this->hasOne(Receptionist::class, 'id', 'id');
    }

    /**
     * Get the cashier profile linked to this user.
     */
    public function cashier()
    {
        return $this->hasOne(Cashier::class, 'id', 'id');
    }

    /**
     * Get the chef profile linked to this user.
     */
    public function chef()
    {
        return $this->hasOne(Chef::class, 'id', 'id');
    }

    /**
     * RBAC: User's permanent assigned roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * RBAC: User's temporary role assignments.
     */
    public function temporaryRoleAssignments()
    {
        return $this->hasMany(TemporaryRoleAssignment::class, 'user_id');
    }

    /**
     * RBAC: Direct user permission assignments.
     */
    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id');
    }

    /**
     * RBAC: Direct permissions relationship.
     */
    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
                    ->withPivot('granted_by', 'starts_at', 'expires_at')
                    ->withTimestamps();
    }

    /**
     * RBAC: Helper method to check if user has a permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasPermission($this, $permissionSlug);
    }

    /**
     * RBAC: Helper method to check if user has any permission.
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasAnyPermission($this, $permissionSlugs);
    }

    /**
     * RBAC: Helper method to check if user has all permissions.
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasAllPermissions($this, $permissionSlugs);
    }

    /**
     * RBAC: Helper method to check if user has a role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return app(\App\Services\AuthorizationService::class)->hasRole($this, $roleSlug);
    }

    /**
     * RBAC: Helper method to get effective permissions list.
     */
    public function getEffectivePermissions(): array
    {
        return app(\App\Services\AuthorizationService::class)->getEffectivePermissions($this);
    }
}

