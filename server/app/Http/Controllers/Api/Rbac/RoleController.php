<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Hotel;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use App\Services\TenantContext;
use App\Services\TenantRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    protected AuthorizationService $authService;
    protected TenantRoleService $tenantRoleService;

    public function __construct(AuthorizationService $authService, TenantRoleService $tenantRoleService)
    {
        $this->authService = $authService;
        $this->tenantRoleService = $tenantRoleService;
    }
    

    /**
     * Resolve the current authorized hotel ID for this request.
     */
    protected function resolveHotelId(Request $request): ?string
    {
        $hotelId = app(TenantContext::class)->getHotelId()
            ?: $request->header('X-Hotel-ID')
            ?: $request->query('hotel_id');

        $user = $request->user();
        if ($hotelId && $user && !$user->isPlatformAdmin()) {
            if (!$user->belongsToHotel($hotelId)) {
                return null;
            }
        }

        if (!$hotelId && $user && !$user->isPlatformAdmin()) {
            $membership = $user->hotelMemberships()->where('is_active', true)->first();
            $hotelId = $membership?->hotel_id;
        }

        return $hotelId;
    }

    /**
     * Verify that the requested role strictly belongs to the current tenant hotel.
     */
    protected function verifyRoleAccess(Role $role, Request $request): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user && $user->isPlatformAdmin()) {
            return null; // Platform super admin has unrestricted master access
        }

        $hotelId = $this->resolveHotelId($request);
        if (!$hotelId) {
            return response()->json([
                'success' => false,
                'message' => 'No active hotel context found. Please specify a hotel.',
            ], 403);
        }

        if ($role->hotel_id !== $hotelId) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: This role belongs to a different hotel tenant.',
            ], 403);
        }

        return null;
    }
    public function index(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $user = $request->user();

        // Ensure default roles exist for this hotel if newly created (only if no roles exist yet)
        if ($hotelId) {
            $hasRoles = Role::withoutTenant()->where('hotel_id', $hotelId)->exists();
            if (!$hasRoles) {
                $hotel = Hotel::find($hotelId);
                if ($hotel) {
                    $this->tenantRoleService->provisionRolesForHotel($hotel);
                }
            }
        }

        $query = Role::withoutTenant()
            ->with(['permissions:id,name,slug,module,action'])
            ->withCount(['permissions']);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        } elseif ($user && $user->isPlatformAdmin()) {
            // Super admin can see global template roles or filter by hotel
            if ($request->filled('hotel_id')) {
                $query->where('hotel_id', $request->input('hotel_id'));
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Hotel context required.',
            ], 403);
        }

        $roles = $query->orderBy('name')->get();

        // Calculate users count per role within this hotel context in optimized aggregate batches
        if ($hotelId && $roles->isNotEmpty()) {
            $userRoleCounts = DB::table('user_roles')
                ->where('hotel_id', $hotelId)
                ->whereIn('role_id', $roles->pluck('id'))
                ->select('role_id', DB::raw('count(*) as aggregate'))
                ->groupBy('role_id')
                ->pluck('aggregate', 'role_id');

            $hotelUserCountsByRoleId = DB::table('hotel_users')
                ->where('hotel_id', $hotelId)
                ->whereIn('role_id', $roles->pluck('id'))
                ->select('role_id', DB::raw('count(*) as aggregate'))
                ->groupBy('role_id')
                ->pluck('aggregate', 'role_id');

            $roleSlugs = $roles->pluck('slug')->filter()->map(fn($s) => strtolower(trim($s)))->unique()->values();

            $hotelUserCountsBySlug = DB::table('hotel_users')
                ->where('hotel_id', $hotelId)
                ->whereIn(DB::raw('LOWER(role)'), $roleSlugs)
                ->select(DB::raw('LOWER(role) as slug'), DB::raw('count(*) as aggregate'))
                ->groupBy(DB::raw('LOWER(role)'))
                ->pluck('aggregate', 'slug');

            $roles->transform(function ($role) use ($userRoleCounts, $hotelUserCountsByRoleId, $hotelUserCountsBySlug) {
                $roleId = $role->id;
                $slug = strtolower($role->slug ?? '');

                $count = $userRoleCounts[$roleId]
                    ?? $hotelUserCountsByRoleId[$roleId]
                    ?? $hotelUserCountsBySlug[$slug]
                    ?? 0;

                $role->users_count = (int) $count;
                return $role;
            });
        }

        return response()->json([
            'success' => true,
            'hotel_id' => $hotelId,
            'data' => $roles,
        ]);
    }

    /**
     * Get list of active roles for dynamic UI options in the current hotel.
     */
    public function getActiveRoles(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $user = $request->user();

        $query = Role::withoutTenant()
            ->where('is_active', true)
            ->with(['permissions:id,name,slug,module,action'])
            ->withCount(['permissions']);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        } elseif ($user && $user->isPlatformAdmin()) {
            if ($request->filled('hotel_id')) {
                $query->where('hotel_id', $request->input('hotel_id'));
            }
        } else {
            // Fallback for public registration / unauthenticated: return standard system template roles
            $query->whereNull('hotel_id')->orWhere('is_system', true);
        }

        $roles = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'hotel_id' => $hotelId,
            'data' => $roles,
        ]);
    }

    /**
     * Store a newly created role within the current hotel.
     */
    public function store(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $user = $request->user();

        if (!$hotelId && (!$user || !$user->isPlatformAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create role without active hotel context.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Enforce uniqueness within this hotel
        $existingSlugQuery = Role::withoutTenant()->where('slug', $slug);
        if ($hotelId) {
            $existingSlugQuery->where('hotel_id', $hotelId);
        } else {
            $existingSlugQuery->whereNull('hotel_id');
        }

        if ($existingSlugQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => "A role with slug '{$slug}' already exists in this hotel.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $role = Role::withoutTenant()->create([
                'hotel_id' => $hotelId,
                'name' => $validated['name'],
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
                'message' => 'Role created successfully for hotel',
                'data' => $role->load(['permissions']),
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
     * Display the specified role.
     */
    public function show(Request $request, Role $role)
    {
        if ($denied = $this->verifyRoleAccess($role, $request)) {
            return $denied;
        }

        $role->load(['permissions']);

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * Update the specified role within this hotel.
     */
    public function update(Request $request, Role $role)
    {
        if ($denied = $this->verifyRoleAccess($role, $request)) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|required|boolean',
        ]);

        // Prevent deactivating critical system role 'admin'
        if ($role->slug === 'admin' && isset($validated['is_active']) && !$validated['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'The Admin role cannot be deactivated.',
            ], 422);
        }

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }

        $oldValues = $role->toArray();
        $role->update($updateData);

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
            'data' => $role->load(['permissions']),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, Role $role)
    {
        if ($denied = $this->verifyRoleAccess($role, $request)) {
            return $denied;
        }

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted. You may deactivate custom roles instead.',
            ], 422);
        }

        $assignedCount = DB::table('hotel_users')->where('role_id', $role->id)->count()
            + DB::table('user_roles')->where('role_id', $role->id)->count();

        if ($assignedCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role because users are currently assigned to it in this hotel.',
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
    public function getPermissions(Request $request, Role $role)
    {
        if ($denied = $this->verifyRoleAccess($role, $request)) {
            return $denied;
        }

        $permissions = $role->permissions;
        return response()->json([
            'success' => true,
            'role_id' => $role->id,
            'hotel_id' => $role->hotel_id,
            'data' => $permissions,
            'permission_ids' => $permissions->pluck('id'),
        ]);
    }

    /**
     * Sync permissions strictly for a hotel-specific role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        // 1. Enforce strict hotel ownership check
        if ($denied = $this->verifyRoleAccess($role, $request)) {
            return $denied;
        }

        $validated = $request->validate([
            'permission_ids' => 'present|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $oldPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();
        
        // 2. Sync permissions ONLY to this hotel-specific role record
        $role->permissions()->sync($validated['permission_ids']);

        // 3. Invalidate role cache strictly for users in this hotel
        $this->authService->invalidateRoleCache($role);

        RbacAuditLog::log(
            $request->user()?->id,
            'role.permissions_updated',
            'Role',
            (string)$role->id,
            ['permission_ids' => $oldPermissionIds, 'hotel_id' => $role->hotel_id],
            ['permission_ids' => $validated['permission_ids'], 'hotel_id' => $role->hotel_id]
        );

        return response()->json([
            'success' => true,
            'message' => "Permissions updated successfully for {$role->name} in this hotel",
            'role_id' => $role->id,
            'hotel_id' => $role->hotel_id,
            'data' => $role->load('permissions'),
        ]);
    }
}
