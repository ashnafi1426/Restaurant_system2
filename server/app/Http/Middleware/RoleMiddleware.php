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

        // 1. Admin user has full system access
        if ($this->authService->hasRole($user, 'admin') || strtolower($user->role ?? '') === 'admin') {
            return $next($request);
        }

        // 2. Specific role match (case-insensitive)
        $allowedRoles = array_map('trim', explode('|', strtolower($roles)));

        if ($this->authService->hasAnyRole($user, $allowedRoles)) {
            return $next($request);
        }

        // 3. Dynamic Custom Role Support:
        // Any active role created by Admin in DB (e.g. Gebere, Balager, Supervisor, etc.)
        // is granted access to operational staff APIs
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
