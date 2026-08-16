<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRoleController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Get user list with assigned roles and temporary roles.
     */
    public function index(Request $request)
    {
        $users = User::with(['roles', 'temporaryRoleAssignments.role'])
            ->orderBy('first_name')
            ->get();

        $data = $users->map(function ($user) {
            $effectivePermissions = $this->authService->getEffectivePermissions($user);
            $activeRoles = $this->authService->getActiveRoles($user);
            $tempAssignments = $this->authService->getActiveTemporaryRoles($user);
            $primaryRoleModel = $user->roles->first(fn($r) => $r->pivot->is_primary) ?? $user->roles->first();

            $directPermissions = \App\Models\UserPermission::where('user_id', $user->id)->get();

            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'legacy_role' => $user->role,
                'primary_role_name' => $primaryRoleModel ? $primaryRoleModel->name : ucfirst($user->role ?? 'Guest'),
                'primary_role_slug' => $primaryRoleModel ? $primaryRoleModel->slug : strtolower($user->role ?? 'guest'),
                'is_active' => $user->is_active,
                'roles' => $user->roles->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                    'is_primary' => $r->pivot->is_primary ?? false,
                ]),
                'active_roles' => $activeRoles->pluck('name')->toArray(),
                'temporary_assignments' => $tempAssignments->map(fn($ta) => [
                    'id' => $ta->id,
                    'role_id' => $ta->role_id,
                    'role_name' => $ta->role->name ?? '',
                    'starts_at' => $ta->starts_at->toIso8601String(),
                    'expires_at' => $ta->expires_at->toIso8601String(),
                    'reason' => $ta->reason,
                ]),
                'direct_permissions_count' => $directPermissions->count(),
                'direct_permission_ids' => $directPermissions->pluck('permission_id')->toArray(),
                'effective_permissions_count' => count($effectivePermissions),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get roles for a specific user.
     */
    public function getUserRoles(User $user)
    {
        $roles = $user->roles;
        $activeRoles = $this->authService->getActiveRoles($user);
        $effectivePermissions = $this->authService->getEffectivePermissions($user);
        $tempAssignments = $this->authService->getActiveTemporaryRoles($user);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'legacy_role' => $user->role,
            ],
            'roles' => $roles,
            'active_roles' => $activeRoles,
            'temporary_assignments' => $tempAssignments,
            'effective_permissions' => $effectivePermissions,
        ]);
    }

    /**
     * Assign roles to a user.
     */
    public function assignRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'primary_role_id' => 'nullable|exists:roles,id',
        ]);

        // Last Admin Check
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && $user->roles()->where('role_id', $adminRole->id)->exists() && !in_array($adminRole->id, $validated['role_ids'])) {
            $activeAdminCount = User::whereHas('roles', fn($q) => $q->where('slug', 'admin'))->count();
            if ($activeAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove Admin role: System must have at least one active Admin user.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $oldRoleIds = $user->roles()->pluck('roles.id')->toArray();
            
            $pivotData = [];
            $primaryRoleId = $validated['primary_role_id'] ?? $validated['role_ids'][0];

            foreach ($validated['role_ids'] as $roleId) {
                $pivotData[$roleId] = [
                    'is_primary' => ($roleId == $primaryRoleId),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $user->roles()->sync($pivotData);

            // Sync legacy role column with primary role for backward compatibility
            $primaryRoleModel = Role::find($primaryRoleId);
            if ($primaryRoleModel) {
                $user->update(['role' => $primaryRoleModel->slug]);
            }

            $this->authService->invalidateUserCache($user->id);

            RbacAuditLog::log(
                $request->user()?->id,
                'user.roles_assigned',
                'User',
                (string)$user->id,
                ['role_ids' => $oldRoleIds],
                ['role_ids' => $validated['role_ids'], 'primary_role_id' => $primaryRoleId]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Roles assigned successfully to user ' . $user->full_name,
                'data' => $user->load('roles'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, User $user, Role $role)
    {
        // Last Admin Check
        if ($role->slug === 'admin') {
            $adminCount = User::whereHas('roles', fn($q) => $q->where('slug', 'admin'))->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove Admin role: System must have at least one active Admin user.',
                ], 422);
            }
        }

        $user->roles()->detach($role->id);

        // Update legacy role string if needed
        $remainingPrimary = $user->roles()->wherePivot('is_primary', true)->first() ?? $user->roles()->first();
        if ($remainingPrimary) {
            $user->update(['role' => $remainingPrimary->slug]);
        }

        $this->authService->invalidateUserCache($user->id);

        RbacAuditLog::log(
            $request->user()?->id,
            'user.role_removed',
            'User',
            (string)$user->id,
            ['removed_role_id' => $role->id, 'removed_role_name' => $role->name],
            null
        );

        return response()->json([
            'success' => true,
            'message' => "Role {$role->name} removed from user {$user->full_name}",
        ]);
    }
}
