<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isPlatformAdmin()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Platform administrator access required.',
            ], 403);
        }

        return $next($request);
    }
}
