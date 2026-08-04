<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link email.
     * 
     * POST /api/forgot-password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Rate limiting: 3 attempts per hour per email
        $key = 'forgot-password:' . $request->email;
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            
            return response()->json([
                'success' => false,
                'message' => "Too many password reset requests. Please try again in {$minutes} minutes."
            ], 429);
        }
        
        RateLimiter::hit($key, 3600); // 1 hour
        
        try {
            $user = User::where('email', $request->email)->first();
            
            // Check if user needs activation first
            if ($user->needsActivation()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not activated yet. Please check your email for the activation link.',
                    'needs_activation' => true
                ], 400);
            }
            
            // Create password reset token
            $token = Str::random(60);
            
            // Delete any existing tokens for this email
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            
            // Insert new token
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]);
            
            // Send password reset email
            Mail::to($user->email)->send(new PasswordResetMail($user, $token));
            
            Log::info('Password reset email sent', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'We have emailed your password reset link! Please check your inbox.'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email. Please try again.'
            ], 500);
        }
    }

    /**
     * Reset user password.
     * 
     * POST /api/reset-password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Rate limiting: 5 attempts per minute per IP
        $key = 'reset-password:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again later.'
            ], 429);
        }
        
        RateLimiter::hit($key, 60);
        
        try {
            // Find password reset record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();
            
            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired password reset token.',
                    'error_type' => 'invalid_token'
                ], 400);
            }
            
            // Verify token
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password reset token.',
                    'error_type' => 'invalid_token'
                ], 400);
            }
            
            // Check if token is expired (60 minutes)
            $tokenAge = now()->diffInMinutes($resetRecord->created_at);
            if ($tokenAge > 60) {
                // Delete expired token
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Password reset token has expired. Please request a new one.',
                    'error_type' => 'expired'
                ], 400);
            }
            
            // Find user and update password
            $user = User::where('email', $request->email)->first();
            
            $user->update([
                'password_hash' => Hash::make($request->password)
            ]);
            
            // Delete the used token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            
            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully! You can now log in with your new password.'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to reset password', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password. Please try again.'
            ], 500);
        }
    }

    /**
     * Verify password reset token.
     * 
     * POST /api/verify-reset-token
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email'
        ]);
        
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();
        
        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 400);
        }
        
        // Check expiration
        $tokenAge = now()->diffInMinutes($resetRecord->created_at);
        if ($tokenAge > 60) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Token is valid'
        ]);
    }
}
