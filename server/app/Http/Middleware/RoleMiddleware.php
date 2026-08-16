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

        $allowedRoles = explode('|', $roles);

        if (!$this->authService->hasAnyRole($user, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Required role: ' . $roles
            ], 403);
        }

        return $next($request);
    }
}
