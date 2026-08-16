<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class AuthorizationService
{
    private const CACHE_TTL_SECONDS = 600; // 10 minutes

    /**
     * Get user's primary active role.
     */
    public function getUserRoles(User $user): Collection
    {
        // 1. Check primary role from user_roles pivot
        $primaryRole = $user->roles()
            ->wherePivot('is_primary', true)
            ->where('is_active', true)
            ->first();

        if ($primaryRole) {
            return collect([$primaryRole]);
        }

        // 2. Fallback to any active role assigned to user
        $activeRole = $user->roles()->where('is_active', true)->first();
        if ($activeRole) {
            return collect([$activeRole]);
        }

        // 3. Auto-heal/sync if user has role string column
        if (!empty($user->role)) {
            $targetRoleStr = strtolower($user->role);
            $roleModel = Role::whereRaw('LOWER(slug) = ?', [$targetRoleStr])
                ->orWhereRaw('LOWER(name) = ?', [$targetRoleStr])
                ->where('is_active', true)
                ->first();

            if ($roleModel) {
                try {
                    $user->roles()->syncWithoutDetaching([
                        $roleModel->id => ['is_primary' => true]
                    ]);
                } catch (\Exception $e) {
                    // Ignore duplicate key if concurrently written
                }
                return collect([$roleModel]);
            }
        }

        return collect();
    }

    /**
     * Temporary role assignments removed per requirement.
     */
    public function getActiveTemporaryRoles(User $user): Collection
    {
        return collect();
    }

    /**
     * Get primary active role for the user.
     */
    public function getActiveRoles(User $user): Collection
    {
        return $this->getUserRoles($user);
    }

    /**
     * Get effective permission slugs strictly from the user's assigned role.
     */
    public function getEffectivePermissions(User $user): array
    {
        $cacheKey = "user_permissions_{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($user) {
            $roles = $this->getUserRoles($user);

            if ($roles->isEmpty()) {
                return [];
            }

            // Collect permissions strictly from the user's assigned role
            $permissionSlugs = [];
            foreach ($roles as $role) {
                $rolePermissions = $role->permissions()
                    ->where('is_active', true)
                    ->pluck('slug')
                    ->toArray();
                $permissionSlugs = array_merge($permissionSlugs, $rolePermissions);
            }

            return array_values(array_unique(array_map('strtolower', $permissionSlugs)));
        });
    }

    /**
     * Check if user has a specific permission based strictly on their role.
     */
    public function hasPermission(User $user, string $permissionSlug): bool
    {
        if ($this->hasRole($user, 'admin')) {
            return true;
        }

        $effectivePermissions = $this->getEffectivePermissions($user);
        return in_array(strtolower($permissionSlug), $effectivePermissions, true);
    }

    /**
     * Check if user has ANY of the specified permissions based strictly on their role.
     */
    public function hasAnyPermission(User $user, array $permissionSlugs): bool
    {
        if ($this->hasRole($user, 'admin')) {
            return true;
        }

        $effectivePermissions = $this->getEffectivePermissions($user);
        foreach ($permissionSlugs as $slug) {
            if (in_array(strtolower($slug), $effectivePermissions, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has ALL of the specified permissions based strictly on their role.
     */
    public function hasAllPermissions(User $user, array $permissionSlugs): bool
    {
        if ($this->hasRole($user, 'admin')) {
            return true;
        }

        $effectivePermissions = $this->getEffectivePermissions($user);
        foreach ($permissionSlugs as $slug) {
            if (!in_array(strtolower($slug), $effectivePermissions, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(User $user, string $roleSlug): bool
    {
        $targetSlug = strtolower($roleSlug);
        $activeRoles = $this->getActiveRoles($user);
        foreach ($activeRoles as $r) {
            if (strtolower($r->slug) === $targetSlug || strtolower($r->name) === $targetSlug) {
                return true;
            }
        }

        return strtolower($user->role ?? '') === $targetSlug;
    }

    /**
     * Check if user has ANY of the specified roles.
     */
    public function hasAnyRole(User $user, array $roleSlugs): bool
    {
        foreach ($roleSlugs as $slug) {
            if ($this->hasRole($user, $slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Invalidate permission cache for a user.
     */
    public function invalidateUserCache(string $userId): void
    {
        Cache::forget("user_permissions_{$userId}");
    }

    /**
     * Invalidate cache for all users assigned to a role.
     */
    public function invalidateRoleCache(Role $role): void
    {
        Cache::flush();
        $userIds = $role->users()->pluck('users.id')->toArray();
        $legacyUserIds = User::whereRaw('LOWER(role) = ?', [strtolower($role->slug)])
            ->orWhereRaw('LOWER(role) = ?', [strtolower($role->name)])
            ->pluck('id')
            ->toArray();

        $allUserIds = array_unique(array_merge($userIds, $legacyUserIds));
        foreach ($allUserIds as $userId) {
            $this->invalidateUserCache((string)$userId);
        }
    }
}
