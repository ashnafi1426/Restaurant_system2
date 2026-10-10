<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuthorizationService;

class RoleMiddleware
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $userRole = strtolower($user->role ?? '');

        if ($user->isPlatformAdmin() || $this->authService->hasRole($user, 'admin') || $userRole === 'admin') {
            return $next($request);
        }

        $allowedRoles = array_map('trim', explode('|', strtolower($roles)));

        $isStaffRole = in_array($userRole, ['staff', 'waiter', 'manager', 'chef', 'receptionist', 'kitchen', 'cashier']);
        if (in_array('staff', $allowedRoles) && $isStaffRole) {
            return $next($request);
        }

        if (in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        if ($this->authService->hasAnyRole($user, $allowedRoles)) {
            return $next($request);
        }

        $activeRoles = $this->authService->getActiveRoles($user);
        if ($activeRoles->isNotEmpty()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Required role: ' . $roles
        ], 403);
    }
}

