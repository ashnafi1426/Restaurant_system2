<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display a listing of all permissions, grouped by module.
     */
    public function index()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();

        $grouped = $permissions->groupBy('module');

        return response()->json([
            'success' => true,
            'data' => $permissions,
            'grouped' => $grouped,
        ]);
    }

    /**
     * Store a newly created permission dynamically.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:permissions,slug',
            'module' => 'required|string|max:100',
            'action' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $module = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['module'])));
        $action = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['action'])));

        if (!empty($validated['slug'])) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9_.-]/', '', $validated['slug'])));
        } else {
            $slug = "{$module}.{$action}";
        }

        if (Permission::where('slug', $slug)->exists()) {
            $slug = $slug . '_' . time();
        }

        $permission = Permission::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'module' => $module,
            'action' => $action,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['role_ids'])) {
            $permission->roles()->sync($validated['role_ids']);
        } else {
            $adminRole = \App\Models\Role::where('slug', 'admin')->first();
            if ($adminRole) {
                $permission->roles()->syncWithoutDetaching([$adminRole->id]);
            }
        }

        \Illuminate\Support\Facades\Cache::flush();

        RbacAuditLog::log(
            $request->user()?->id,
            'permission.created',
            'Permission',
            (string)$permission->id,
            null,
            $permission->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission->load('roles'),
        ], 201);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        return response()->json([
            'success' => true,
            'data' => $permission->load('roles'),
        ]);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'module' => 'sometimes|required|string|max:100',
            'action' => 'sometimes|required|string|max:100',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|required|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $updateData = [];
        if (isset($validated['name'])) $updateData['name'] = $validated['name'];
        if (array_key_exists('description', $validated)) $updateData['description'] = $validated['description'];
        if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];

        if (isset($validated['module'])) {
            $updateData['module'] = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['module'])));
        }
        if (isset($validated['action'])) {
            $updateData['action'] = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['action'])));
        }

        if (!empty($validated['slug'])) {
            $updateData['slug'] = strtolower(trim(preg_replace('/[^a-zA-Z0-9_.-]/', '', $validated['slug'])));
        } elseif (isset($updateData['module']) && isset($updateData['action'])) {
            $updateData['slug'] = "{$updateData['module']}.{$updateData['action']}";
        }

        $oldValues = $permission->toArray();
        $permission->update($updateData);

        if (isset($validated['role_ids'])) {
            $permission->roles()->sync($validated['role_ids']);
        }

        \Illuminate\Support\Facades\Cache::flush();

        RbacAuditLog::log(
            $request->user()?->id,
            'permission.updated',
            'Permission',
            (string)$permission->id,
            $oldValues,
            $permission->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission->load('roles'),
        ]);
    }

    /**
     * Deactivate or delete permission safely.
     */
    public function destroy(Request $request, Permission $permission)
    {
        // Safe deactivation preferred over deletion if assigned to roles
        if ($permission->roles()->count() > 0) {
            $permission->update(['is_active' => false]);
            \Illuminate\Support\Facades\Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Permission has assigned roles and was deactivated instead of deleted.',
            ]);
        }

        $oldValues = $permission->toArray();
        $permission->delete();
        \Illuminate\Support\Facades\Cache::flush();

        RbacAuditLog::log(
            $request->user()?->id,
            'permission.deleted',
            'Permission',
            (string)$permission->id,
            $oldValues,
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ]);
    }
}
