<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\TemporaryRoleAssignment;
use App\Models\User;
use App\Models\Role;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\TenantContext;

class TemporaryRoleController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function index(Request $request)
    {
        $query = TemporaryRoleAssignment::with(['user', 'role', 'assigner']);

        $hotelId = $request->header('X-Hotel-ID')
            ?: app(TenantContext::class)->getHotelId()
            ?: $request->query('hotel_id');

        $isAllHotels = $request->boolean('all_hotels') && $request->user()?->isPlatformAdmin();

        if (!$isAllHotels) {
            if (!$hotelId && $request->user()) {
                $hotelId = $request->user()->hotelMemberships()->first()?->hotel_id;
            }

            if ($hotelId) {
                $query->whereHas('user.hotelMemberships', function ($q) use ($hotelId) {
                    $q->where('hotel_id', $hotelId);
                });
            }
        }

        $assignments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role = Role::findOrFail($validated['role_id']);

        $assignment = TemporaryRoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'starts_at' => Carbon::parse($validated['starts_at']),
            'expires_at' => Carbon::parse($validated['expires_at']),
            'assigned_by' => $request->user()?->id,
            'reason' => $validated['reason'],
            'is_active' => true,
        ]);
        $this->authService->invalidateUserCache($user->id);

        RbacAuditLog::log(
            $request->user()?->id,
            'temporary_role.assigned',
            'User',
            (string)$user->id,
            null,
            [
                'assignment_id' => $assignment->id,
                'role' => $role->name,
                'starts_at' => $assignment->starts_at->toIso8601String(),
                'expires_at' => $assignment->expires_at->toIso8601String(),
                'reason' => $assignment->reason,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Temporary role {$role->name} assigned to {$user->full_name}",
            'data' => $assignment->load(['user', 'role', 'assigner']),
        ], 201);
    }

    public function destroy(Request $request, TemporaryRoleAssignment $temporaryRoleAssignment)
    {
        $temporaryRoleAssignment->update(['is_active' => false]);
        $this->authService->invalidateUserCache($temporaryRoleAssignment->user_id);

        RbacAuditLog::log(
            $request->user()?->id,
            'temporary_role.revoked',
            'User',
            (string)$temporaryRoleAssignment->user_id,
            ['assignment_id' => $temporaryRoleAssignment->id],
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Temporary role assignment revoked successfully.',
        ]);
    }
}
