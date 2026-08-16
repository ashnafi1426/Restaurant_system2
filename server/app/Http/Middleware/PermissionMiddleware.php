<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuthorizationService;

class PermissionMiddleware
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request for permission authorization.
     * Usage in routes: middleware('permission:orders.view|orders.assign')
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Support pipe-separated OR permission checks (e.g., 'orders.view|orders.create')
        $permissionList = explode('|', $permissions);

        if (!$this->authService->hasAnyPermission($user, $permissionList)) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Missing required permission (' . implode(' or ', $permissionList) . ')'
            ], 403);
        }

        return $next($request);
    }
}
