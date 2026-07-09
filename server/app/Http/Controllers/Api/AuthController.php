<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authrequest;
use App\Http\Resources\AuthResource;

class AuthController extends Controller
{
    public function login(Authrequest $request)
    {
        // Log incoming request
        \Log::info('Login attempt', [
            'email' => $request->email,
            'password_length' => strlen($request->password),
            'password_chars' => mb_strlen($request->password),
        ]);

        $user = User::where(
            'email',
            $request->email
        )->first();
        
        if (!$user) {
            \Log::warning('Login: User not found', ['email' => $request->email]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $passwordMatches = Hash::check(
            $request->password,
            $user->password_hash
        );
        
        \Log::info('Login: Password check', [
            'user_email' => $user->email,
            'attempted_password' => $request->password,
            'password_hash' => substr($user->password_hash, 0, 20) . '...',
            'match' => $passwordMatches ? 'YES' : 'NO',
        ]);

        if (!$passwordMatches) {
            \Log::warning('Login: Password mismatch', ['email' => $request->email]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            \Log::warning('Login: Account disabled', ['email' => $request->email]);
            return response()->json([
                'success' => false,
                'message' => 'Account disabled'
            ], 403);
        }

        $user->update([
            'last_login' => now()
        ]);
        $token = $user
            ->createToken('hotel_token')
            ->plainTextToken;

        \Log::info('Login: Success', [
            'user_email' => $user->email,
            'token' => substr($token, 0, 20) . '...',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',

            'token' => $token,

            'user' => new AuthResource($user)
        ]);
    }

    /**
     * Current User
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => new AuthResource(
                $request->user()
            )
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
