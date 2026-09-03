<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Permission;
use App\Models\UserPermission;
use App\Models\Role;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDirectPermissionController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Get user's primary role, inherited role permissions, direct permissions, and system permissions catalog.
     */
    public function getUserPermissions(Request $request, User $user)
    {
        $hotelId = app(\App\Services\TenantContext::class)->getHotelId()
            ?: $request->header('X-Hotel-ID')
            ?: $request->query('hotel_id');

        $activeRoles = $this->authService->getActiveRoles($user, $hotelId);
        $primaryRole = $activeRoles->first();

        // Role permissions
        $rolePermissions = [];
        if ($primaryRole) {
            $rolePermissions = $primaryRole->permissions()
                ->where('is_active', true)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'module' => $p->module,
                    'action' => $p->action,
                ]);
        }

        // Direct user permissions for this hotel
        $directQuery = UserPermission::with('permission', 'grantor')
            ->where('user_id', $user->id);
        if ($hotelId) {
            $directQuery->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
            });
        }
        $directUserPermissions = $directQuery->get()
            ->map(fn($up) => [
                'id' => $up->id,
                'permission_id' => $up->permission_id,
                'name' => $up->permission->name ?? '',
                'slug' => $up->permission->slug ?? '',
                'module' => $up->permission->module ?? '',
                'action' => $up->permission->action ?? '',
                'granted_by' => $up->grantor ? $up->grantor->full_name : null,
                'starts_at' => $up->starts_at ? $up->starts_at->toIso8601String() : null,
                'expires_at' => $up->expires_at ? $up->expires_at->toIso8601String() : null,
                'is_active' => ($up->starts_at === null || $up->starts_at <= now())
                    && ($up->expires_at === null || $up->expires_at >= now()),
            ]);

        // Effective permission slugs
        $effectivePermissions = $this->authService->getEffectivePermissions($user, $hotelId);

        // System all permissions grouped by module
        $allPermissionsGrouped = Permission::where('is_active', true)
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module')
            ->map(function ($items, $module) {
                return [
                    'module' => ucfirst($module),
                    'module_key' => $module,
                    'permissions' => $items->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                        'module' => $p->module,
                        'action' => $p->action,
                        'description' => $p->description,
                    ])->values()
                ];
            })->values();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'legacy_role' => $user->role,
                'is_active' => $user->is_active,
            ],
            'primary_role' => $primaryRole ? [
                'id' => $primaryRole->id,
                'name' => $primaryRole->name,
                'slug' => $primaryRole->slug,
                'description' => $primaryRole->description,
            ] : null,
            'role_permissions' => $rolePermissions,
            'direct_permissions' => $directUserPermissions,
            'effective_permissions' => $effectivePermissions,
            'effective_permissions_count' => count($effectivePermissions),
            'system_permissions_grouped' => $allPermissionsGrouped,
        ]);
    }

    /**
     * Assign/sync direct permissions for a specific user.
     * Keeps primary role unchanged.
     */
    public function assignDirectPermissions(Request $request, User $user)
    {
        $hotelId = app(\App\Services\TenantContext::class)->getHotelId()
            ?: $request->header('X-Hotel-ID')
            ?: $request->query('hotel_id');

        $validated = $request->validate([
            'permission_ids' => 'present|array',
            'permission_ids.*' => 'exists:permissions,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        DB::beginTransaction();
        try {
            $grantorId = $request->user()?->id;

            // Fetch old direct permissions for audit
            $oldPermQuery = UserPermission::where('user_id', $user->id);
            if ($hotelId) {
                $oldPermQuery->where('hotel_id', $hotelId);
            }
            $oldPermissions = $oldPermQuery->pluck('permission_id')->toArray();

            $newPermissionIds = array_unique($validated['permission_ids']);

            // Delete removed direct permissions for this hotel
            $deleteQuery = UserPermission::where('user_id', $user->id)
                ->whereNotIn('permission_id', $newPermissionIds);
            if ($hotelId) {
                $deleteQuery->where('hotel_id', $hotelId);
            }
            $deleteQuery->delete();

            // Insert or update direct permissions with hotel_id
            foreach ($newPermissionIds as $permId) {
                UserPermission::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'permission_id' => $permId,
                        'hotel_id' => $hotelId,
                    ],
                    [
                        'granted_by' => $grantorId,
                        'starts_at' => $validated['starts_at'] ?? null,
                        'expires_at' => $validated['expires_at'] ?? null,
                        'updated_at' => now(),
                    ]
                );
            }

            // Invalidate authorization cache for this user
            $this->authService->invalidateUserCache($user->id, $hotelId);

            // Audit log
            RbacAuditLog::log(
                $grantorId,
                'user.direct_permissions_updated',
                'User',
                (string)$user->id,
                ['old_permission_ids' => $oldPermissions],
                ['new_permission_ids' => $newPermissionIds]
            );

            DB::commit();

            $effectivePermissions = $this->authService->getEffectivePermissions($user);

            return response()->json([
                'success' => true,
                'message' => "Direct permissions successfully updated for {$user->full_name}",
                'direct_permissions_count' => count($newPermissionIds),
                'effective_permissions' => $effectivePermissions,
                'effective_permissions_count' => count($effectivePermissions),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save direct permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a single direct permission from a user.
     */
    public function removeDirectPermission(Request $request, User $user, Permission $permission)
    {
        DB::beginTransaction();
        try {
            UserPermission::where('user_id', $user->id)
                ->where('permission_id', $permission->id)
                ->delete();

            $this->authService->invalidateUserCache($user->id);

            RbacAuditLog::log(
                $request->user()?->id,
                'user.direct_permission_removed',
                'User',
                (string)$user->id,
                ['removed_permission_id' => $permission->id, 'permission_slug' => $permission->slug],
                null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Direct permission '{$permission->name}' removed from {$user->full_name}",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove direct permission: ' . $e->getMessage(),
            ], 500);
        }
    }
}
