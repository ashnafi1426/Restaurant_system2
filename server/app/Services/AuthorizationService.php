<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\HotelUser;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AuthorizationService
{
    private const CACHE_TTL_SECONDS = 600;
    public function getUserRoles(User $user, ?string $hotelId = null): Collection
    {
        $targetHotelId = $hotelId ?: app(TenantContext::class)->getHotelId();

        if ($targetHotelId) {
            $membership = HotelUser::where('hotel_id', $targetHotelId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if ($membership) {
                if ($membership->role_id) {
                    $roleById = Role::withoutTenant()
                        ->where('id', $membership->role_id)
                        ->where('is_active', true)
                        ->first();

                    if ($roleById) {
                        return collect([$roleById]);
                    }
                }

                if (!empty($membership->role)) {
                    $targetRoleStr = strtolower(trim($membership->role));
                    $hotelScopedRole = Role::withoutTenant()
                        ->where('hotel_id', $targetHotelId)
                        ->where('is_active', true)
                        ->where(function ($q) use ($targetRoleStr) {
                            $q->whereRaw('LOWER(slug) = ?', [$targetRoleStr])
                              ->orWhereRaw('LOWER(name) = ?', [$targetRoleStr]);
                        })
                        ->first();

                    if ($hotelScopedRole) {
                        $membership->update(['role_id' => $hotelScopedRole->id]);
                        return collect([$hotelScopedRole]);
                    }
                }
            }

            $hotelRole = Role::withoutTenant()
                ->where('hotel_id', $targetHotelId)
                ->where('is_active', true)
                ->whereHas('users', function ($q) use ($user, $targetHotelId) {
                    $q->where('user_roles.user_id', $user->id)
                      ->where('user_roles.hotel_id', $targetHotelId);
                })
                ->first();

            if ($hotelRole) {
                return collect([$hotelRole]);
            }

            if ($membership && !empty($membership->role)) {
                $targetRoleStr = strtolower(trim($membership->role));
                $templateRole = Role::withoutTenant()
                    ->whereNull('hotel_id')
                    ->where('is_active', true)
                    ->where(function ($q) use ($targetRoleStr) {
                        $q->whereRaw('LOWER(slug) = ?', [$targetRoleStr])
                          ->orWhereRaw('LOWER(name) = ?', [$targetRoleStr]);
                    })
                    ->first();

                if ($templateRole) {
                    return collect([$templateRole]);
                }
            }
        }

        if ($user->isPlatformAdmin()) {
            $adminRole = Role::withoutTenant()
                ->where('slug', 'admin')
                ->where('is_active', true)
                ->first();
            if ($adminRole) {
                return collect([$adminRole]);
            }
        }

        $primaryRole = $user->roles()
            ->withoutGlobalScopes()
            ->wherePivot('is_primary', true)
            ->where('roles.is_active', true)
            ->first();

        if ($primaryRole) {
            return collect([$primaryRole]);
        }

        $anyMembership = $user->hotelMemberships()->where('is_active', true)->first();
        if ($anyMembership && !empty($anyMembership->role)) {
            $targetRoleStr = strtolower(trim($anyMembership->role));
            $roleModel = Role::withoutTenant()
                ->where(function ($q) use ($anyMembership) {
                    $q->where('hotel_id', $anyMembership->hotel_id)->orWhereNull('hotel_id');
                })
                ->where('is_active', true)
                ->where(function ($q) use ($targetRoleStr) {
                    $q->whereRaw('LOWER(slug) = ?', [$targetRoleStr])
                      ->orWhereRaw('LOWER(name) = ?', [$targetRoleStr]);
                })
                ->orderByRaw('CASE WHEN hotel_id IS NOT NULL THEN 0 ELSE 1 END')
                ->first();

            if ($roleModel) {
                return collect([$roleModel]);
            }
        }

        if (method_exists($user, 'manager') && $user->manager()->exists()) {
            $mgrRole = Role::withoutTenant()
                ->where('slug', 'manager')
                ->where('is_active', true)
                ->orderByRaw('CASE WHEN hotel_id IS NOT NULL THEN 0 ELSE 1 END')
                ->first();
            if ($mgrRole) return collect([$mgrRole]);
        }

        if (!empty($user->role)) {
            $targetRoleStr = strtolower($user->role);
            $roleModel = Role::withoutTenant()
                ->where(function ($q) use ($targetRoleStr) {
                    $q->whereRaw('LOWER(slug) = ?', [$targetRoleStr])
                      ->orWhereRaw('LOWER(name) = ?', [$targetRoleStr]);
                })
                ->where('is_active', true)
                ->orderByRaw('CASE WHEN hotel_id IS NOT NULL THEN 0 ELSE 1 END')
                ->first();

            if ($roleModel) {
                return collect([$roleModel]);
            }
        }

        return collect();
    }

    public function getActiveTemporaryRoles(User $user): Collection
    {
        return collect();
    }

    public function getActiveRoles(User $user, ?string $hotelId = null): Collection
    {
        return $this->getUserRoles($user, $hotelId);
    }

    protected array $requestPermissionsCache = [];

    public function getEffectivePermissions(User $user, ?string $hotelId = null): array
    {
        $targetHotelId = $hotelId ?: app(TenantContext::class)->getHotelId() ?: 'global';
        $runtimeKey = "{$targetHotelId}_{$user->id}";

        if (isset($this->requestPermissionsCache[$runtimeKey])) {
            return $this->requestPermissionsCache[$runtimeKey];
        }

        $effectiveHotelId = $targetHotelId === 'global' ? null : $targetHotelId;
        $roles = $this->getUserRoles($user, $effectiveHotelId);

        if ($roles->isEmpty()) {
            $this->requestPermissionsCache[$runtimeKey] = [];
            return [];
        }

        $permissionSlugs = [];
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions()
                ->where('permissions.is_active', true)
                ->pluck('permissions.slug')
                ->toArray();

            $permissionSlugs = array_merge($permissionSlugs, $rolePermissions);

            $slug = strtolower($role->slug ?? '');
            if (in_array($slug, ['manager', 'admin', 'receptionist', 'waiter', 'cashier', 'chef'])) {
                if (!in_array('dashboard.view', $permissionSlugs, true)) {
                    $permissionSlugs[] = 'dashboard.view';
                }
            }
        }

        if (method_exists($user, 'directPermissions')) {
            $directQuery = $user->directPermissions()->where('permissions.is_active', true);
            if ($effectiveHotelId) {
                $directQuery->where(function ($q) use ($effectiveHotelId) {
                    $q->where('user_permissions.hotel_id', $effectiveHotelId)
                      ->orWhereNull('user_permissions.hotel_id');
                });
            }
            $directPerms = $directQuery->pluck('permissions.slug')->toArray();
            $permissionSlugs = array_merge($permissionSlugs, $directPerms);
        }

        $result = array_values(array_unique(array_map('strtolower', $permissionSlugs)));
        $this->requestPermissionsCache[$runtimeKey] = $result;
        return $result;
    }

    public function hasPermission(User $user, string $permissionSlug, ?string $hotelId = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $target = strtolower($permissionSlug);
        $effectivePermissions = $this->getEffectivePermissions($user, $hotelId);

        return in_array($target, $effectivePermissions, true);
    }

    public function hasAnyPermission(User $user, array $permissionSlugs, ?string $hotelId = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $effectivePermissions = $this->getEffectivePermissions($user, $hotelId);
        foreach ($permissionSlugs as $slug) {
            if (in_array(strtolower($slug), $effectivePermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(User $user, array $permissionSlugs, ?string $hotelId = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $effectivePermissions = $this->getEffectivePermissions($user, $hotelId);
        foreach ($permissionSlugs as $slug) {
            if (!in_array(strtolower($slug), $effectivePermissions, true)) {
                return false;
            }
        }

        return true;
    }

    public function hasRole(User $user, string $roleSlug, ?string $hotelId = null): bool
    {
        $targetSlug = strtolower(trim($roleSlug));
        $activeRoles = $this->getActiveRoles($user, $hotelId);

        foreach ($activeRoles as $r) {
            if (strtolower($r->slug) === $targetSlug || strtolower($r->name) === $targetSlug) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(User $user, array $roleSlugs, ?string $hotelId = null): bool
    {
        foreach ($roleSlugs as $slug) {
            if ($this->hasRole($user, $slug, $hotelId)) {
                return true;
            }
        }
        return false;
    }

    public function invalidateUserCache(string $userId, ?string $hotelId = null): void
    {
        $this->requestPermissionsCache = [];

        if ($hotelId) {
            Cache::forget("user_permissions_{$hotelId}_{$userId}");
        } else {
            Cache::forget("user_permissions_global_{$userId}");
            Cache::forget("user_permissions_{$userId}");

            $hotels = \App\Models\Hotel::pluck('id');
            foreach ($hotels as $hId) {
                Cache::forget("user_permissions_{$hId}_{$userId}");
            }
        }
    }

    public function invalidateRoleCache(Role $role): void
    {
        $hotelId = $role->hotel_id;

        $userIds = DB::table('user_roles')
            ->where('role_id', $role->id)
            ->pluck('user_id')
            ->toArray();

        $membershipUserIds = DB::table('hotel_users')
            ->where('role_id', $role->id)
            ->orWhere(function ($q) use ($role) {
                if ($role->hotel_id) {
                    $q->where('hotel_id', $role->hotel_id)
                      ->where('role', $role->slug);
                }
            })
            ->pluck('user_id')
            ->toArray();

        $allUserIds = array_unique(array_merge($userIds, $membershipUserIds));

        foreach ($allUserIds as $userId) {
            $this->invalidateUserCache((string) $userId, $hotelId);
        }
    }
}

