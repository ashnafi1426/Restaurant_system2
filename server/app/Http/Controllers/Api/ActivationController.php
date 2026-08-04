<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use App\Http\Requests\ResendActivationRequest;
use App\Services\ActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ActivationController extends Controller
{
    protected ActivationService $activationService;

    public function __construct(ActivationService $activationService)
    {
        $this->activationService = $activationService;
    }

    /**
     * Validate activation token.
     * 
     * GET /api/activation/{token}
     *
     * @param string $token
     * @return JsonResponse
     */
    public function validateToken(string $token): JsonResponse
    {
        // Rate limiting: 10 attempts per minute per IP
        $key = 'validate-token:' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again later.'
            ], 429);
        }
        
        RateLimiter::hit($key, 60);
        
        $result = $this->activationService->validateToken($token);
        
        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_type' => $result['error_type'],
                'user' => $result['user'] ?? null
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Valid activation token',
            'user' => [
                'first_name' => $result['user']->first_name,
                'last_name' => $result['user']->last_name,
                'email' => $result['user']->email,
                'role' => $result['user']->role
            ]
        ]);
    }

    /**
     * Activate user account with password.
     * 
     * POST /api/activate-account
     *
     * @param ActivateAccountRequest $request
     * @return JsonResponse
     */
    public function activateAccount(ActivateAccountRequest $request): JsonResponse
    {
        // Rate limiting: 5 attempts per minute per IP
        $key = 'activate-account:' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many activation attempts. Please try again later.'
            ], 429);
        }
        
        RateLimiter::hit($key, 60);
        
        $result = $this->activationService->activateAccount(
            $request->token,
            $request->password
        );
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_type' => $result['error_type'] ?? 'activation_failed'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Account activated successfully! You can now log in.',
            'user' => [
                'id' => $result['user']->id,
                'first_name' => $result['user']->first_name,
                'last_name' => $result['user']->last_name,
                'email' => $result['user']->email,
                'role' => $result['user']->role
            ]
        ], 200);
    }

    /**
     * Resend activation email.
     * 
     * POST /api/resend-activation
     *
     * @param ResendActivationRequest $request
     * @return JsonResponse
     */
    public function resendActivation(ResendActivationRequest $request): JsonResponse
    {
        // Rate limiting: 3 attempts per hour per email
        $key = 'resend-activation:' . $request->email;
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            
            return response()->json([
                'success' => false,
                'message' => "Too many requests. Please try again in {$minutes} minutes."
            ], 429);
        }
        
        RateLimiter::hit($key, 3600); // 1 hour
        
        $result = $this->activationService->resendActivation($request->email);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'A new activation email has been sent. Please check your inbox.',
            'expires_at' => $result['expires_at']
        ], 200);
    }

    /**
     * Check activation status for login attempts.
     * 
     * POST /api/check-activation-status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkActivationStatus(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        
        $user = \App\Models\User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        if ($this->activationService->needsActivation($user)) {
            return response()->json([
                'success' => false,
                'needs_activation' => true,
                'activation_status' => $user->activation_status,
                'message' => 'Account not activated. Please check your email for the activation link.'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'needs_activation' => false,
            'message' => 'Account is activated'
        ]);
    }
}
