<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display a listing of all roles.
     */
    public function index()
    {
        $roles = Role::with(['permissions'])
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get list of active system roles for dynamic UI options.
     */
    public function getActiveRoles()
    {
        $roles = Role::where('is_active', true)
            ->with(['permissions'])
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        if (Role::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . time();
        }

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validated['name'],
                'display_name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'is_system' => false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            RbacAuditLog::log(
                $request->user()?->id,
                'role.created',
                'Role',
                (string)$role->id,
                null,
                $role->toArray()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role->load(['permissions', 'users']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified role with its permissions and assigned users.
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|required|boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['display_name'] = $validated['name'];
        }

        // Prevent deactivating critical system role 'admin'
        if ($role->slug === 'admin' && isset($validated['is_active']) && !$validated['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'The Admin role cannot be deactivated.',
            ], 422);
        }

        $oldValues = $role->toArray();
        $role->update($validated);

        $this->authService->invalidateRoleCache($role);

        RbacAuditLog::log(
            $request->user()?->id,
            'role.updated',
            'Role',
            (string)$role->id,
            $oldValues,
            $role->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role->load(['permissions', 'users']),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, Role $role)
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted. You may deactivate custom roles instead.',
            ], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role because users are currently assigned to it. Reassign or remove users first.',
            ], 422);
        }

        $oldValues = $role->toArray();
        $this->authService->invalidateRoleCache($role);

        $role->delete();

        RbacAuditLog::log(
            $request->user()?->id,
            'role.deleted',
            'Role',
            (string)$role->id,
            $oldValues,
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Get all permission IDs assigned to a role.
     */
    public function getPermissions(Role $role)
    {
        $permissions = $role->permissions;
        return response()->json([
            'success' => true,
            'role_id' => $role->id,
            'data' => $permissions,
            'permission_ids' => $permissions->pluck('id'),
        ]);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids' => 'present|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $oldPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();
        $role->permissions()->sync($validated['permission_ids']);

        $this->authService->invalidateRoleCache($role);

        RbacAuditLog::log(
            $request->user()?->id,
            'role.permissions_updated',
            'Role',
            (string)$role->id,
            ['permission_ids' => $oldPermissionIds],
            ['permission_ids' => $validated['permission_ids']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully for role ' . $role->name,
            'data' => $role->load('permissions'),
        ]);
    }
}
