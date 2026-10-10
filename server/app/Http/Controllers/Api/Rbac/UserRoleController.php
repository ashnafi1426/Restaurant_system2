<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\HotelUser;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\UserPermission;

class UserRoleController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

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

    public function index(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        $user = $request->user();

        if (!$hotelId && (!$user || !$user->isPlatformAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Active hotel context required.',
            ], 403);
        }

        $query = User::with([
            'temporaryRoleAssignments.role',
            'hotelMemberships' => function ($q) use ($hotelId) {
                if ($hotelId) {
                    $q->where('hotel_id', $hotelId);
                }
            }
        ]);

        if ($hotelId) {
            $query->whereHas('hotelMemberships', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            });
        }

        $users = $query->orderBy('first_name')->get();

        $userIds = $users->pluck('id')->filter()->toArray();
        $directPermsGrouped = collect();
        if (!empty($userIds)) {
            $directPermsQuery =UserPermission::whereIn('user_id', $userIds);
            if ($hotelId) {
                $directPermsQuery->where(function ($q) use ($hotelId) {
                    $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
                });
            }
            $directPermsGrouped = $directPermsQuery->get()->groupBy('user_id');
        }

        $data = $users->map(function ($u) use ($hotelId, $directPermsGrouped) {
            $effectivePermissions = $this->authService->getEffectivePermissions($u, $hotelId);
            $activeRoles = $this->authService->getActiveRoles($u, $hotelId);
            $primaryRoleModel = $activeRoles->first();

            $directPermissions = $directPermsGrouped->get($u->id, collect());

            $membership = $u->hotelMemberships->first();
            $membershipRoleStr = $membership?->role ?: $u->role;
            return [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'phone' => $u->phone,
                'legacy_role' => $membershipRoleStr,
                'primary_role_name' => $primaryRoleModel ? $primaryRoleModel->name : ucfirst($membershipRoleStr ?? 'Staff'),
                'primary_role_slug' => $primaryRoleModel ? $primaryRoleModel->slug : strtolower($membershipRoleStr ?? 'staff'),
                'is_active' => $u->is_active,
                'roles' => $activeRoles->map(fn($r) => [
                    'id' => $r->id,
                    'hotel_id' => $r->hotel_id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                    'is_primary' => true,
                ]),
                'active_roles' => $activeRoles->pluck('name')->toArray(),
                'temporary_assignments' => [],
                'direct_permissions_count' => $directPermissions->count(),
                'direct_permission_ids' => $directPermissions->pluck('permission_id')->toArray(),
                'effective_permissions_count' => count($effectivePermissions),
            ];
        });

        return response()->json([
            'success' => true,
            'hotel_id' => $hotelId,
            'data' => $data,
        ]);
    }

    public function getUserRoles(Request $request, User $user)
    {
        $hotelId = $this->resolveHotelId($request);

        $activeRoles = $this->authService->getActiveRoles($user, $hotelId);
        $effectivePermissions = $this->authService->getEffectivePermissions($user, $hotelId);

        return response()->json([
            'success' => true,
            'hotel_id' => $hotelId,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'legacy_role' => $user->getHotelRole($hotelId) ?? $user->role,
            ],
            'roles' => $activeRoles,
            'active_roles' => $activeRoles,
            'temporary_assignments' => [],
            'effective_permissions' => $effectivePermissions,
        ]);
    }

    public function assignRoles(Request $request, User $user)
    {
        $hotelId = $this->resolveHotelId($request);
        $currentUser = $request->user();

        if (!$hotelId && (!$currentUser || !$currentUser->isPlatformAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Active hotel context required.',
            ], 403);
        }

        if ($hotelId && !$user->belongsToHotel($hotelId)) {
            return response()->json([
                'success' => false,
                'message' => 'Target user does not belong to this hotel.',
            ], 422);
        }

        $validated = $request->validate([
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'primary_role_id' => 'nullable|exists:roles,id',
        ]);

        if ($hotelId) {
            $crossTenantRoles = Role::withoutTenant()
                ->whereIn('id', $validated['role_ids'])
                ->where('hotel_id', '!=', $hotelId)
                ->exists();

            if ($crossTenantRoles) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cross-tenant security violation: Selected roles do not belong to this hotel.',
                ], 403);
            }
        }

        if ($hotelId) {
            $adminRole = Role::withoutTenant()->where('hotel_id', $hotelId)->where('slug', 'admin')->first();
            if ($adminRole && $user->belongsToHotel($hotelId)) {
                $userIsAdmin = HotelUser::where('hotel_id', $hotelId)->where('user_id', $user->id)->where('role', 'admin')->exists();
                if ($userIsAdmin && !in_array($adminRole->id, $validated['role_ids'])) {
                    $adminCount = HotelUser::where('hotel_id', $hotelId)->where('role', 'admin')->where('is_active', true)->count();
                    if ($adminCount <= 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot remove Admin role: Hotel must have at least one active Admin user.',
                        ], 422);
                    }
                }
            }
        }
        DB::beginTransaction();
        try {
            $primaryRoleId = $validated['primary_role_id'] ?? $validated['role_ids'][0];
            $primaryRoleModel = Role::withoutTenant()->find($primaryRoleId);

            if ($hotelId) {
                DB::table('user_roles')
                    ->where('hotel_id', $hotelId)
                    ->where('user_id', $user->id)
                    ->delete();

                $inserts = [];
                foreach ($validated['role_ids'] as $rId) {
                    $inserts[] = [
                        'hotel_id' => $hotelId,
                        'user_id' => $user->id,
                        'role_id' => $rId,
                        'is_primary' => ($rId == $primaryRoleId),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('user_roles')->insert($inserts);

                if ($primaryRoleModel) {
                    HotelUser::where('hotel_id', $hotelId)
                        ->where('user_id', $user->id)
                        ->update([
                            'role' => $primaryRoleModel->slug,
                            'role_id' => $primaryRoleModel->id,
                            'is_active' => true,
                        ]);
                }
            }

            $this->authService->invalidateUserCache($user->id, $hotelId);
            if ($hotelId) {
                Cache::forget("rbac_roles:{$hotelId}");
                Cache::forget("rbac_active_roles:{$hotelId}");
            }
            Cache::forget('rbac_roles:platform');

            RbacAuditLog::log(
                $currentUser?->id,
                'user.roles_assigned',
                'User',
                (string)$user->id,
                null,
                ['role_ids' => $validated['role_ids'], 'hotel_id' => $hotelId, 'primary_role_id' => $primaryRoleId]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Roles assigned successfully for {$user->full_name} in this hotel",
                'hotel_id' => $hotelId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function removeRole(Request $request, User $user, Role $role)
    {
        $hotelId = $this->resolveHotelId($request);

        if (!$hotelId && (!$request->user() || !$request->user()->isPlatformAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Active hotel context required.',
            ], 403);
        }

        if ($hotelId && $role->hotel_id !== $hotelId) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Role belongs to a different hotel.',
            ], 403);
        }

        if ($role->slug === 'admin' && $hotelId) {
            $adminCount = HotelUser::where('hotel_id', $hotelId)->where('role', 'admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove Admin role: Hotel must have at least one active Admin user.',
                ], 422);
            }
        }

        DB::table('user_roles')
            ->where('hotel_id', $hotelId)
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->delete();

        $this->authService->invalidateUserCache($user->id, $hotelId);
        if ($hotelId) {
            Cache::forget("rbac_roles:{$hotelId}");
            Cache::forget("rbac_active_roles:{$hotelId}");
        }
        Cache::forget('rbac_roles:platform');

        RbacAuditLog::log(
            $request->user()?->id,
            'user.role_removed',
            'User',
            (string)$user->id,
            ['removed_role_id' => $role->id, 'hotel_id' => $hotelId],
            null
        );

        return response()->json([
            'success' => true,
            'message' => "Role {$role->name} removed from user {$user->full_name} for this hotel",
        ]);
    }
}

