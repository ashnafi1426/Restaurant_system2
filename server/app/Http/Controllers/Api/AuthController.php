<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authrequest;
use App\Http\Resources\AuthResource;

class AuthController extends Controller
{
    public function login(Authrequest $request)
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'password_length' => strlen($request->password),
            'password_chars' => mb_strlen($request->password),
            'timestamp' => now(),
        ]);
        try {
            $user = User::where(
                'email',
                $request->email
            )->first();
            if (!$user) {
                Log::warning('Login: User not found', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
            // Check if account needs activation (only for accounts without an active password)
            if ($user->needsActivation() && empty($user->password_hash)) {
                Log::warning('Login: Account not activated', [
                    'email' => $request->email,
                    'activation_status' => $user->activation_status
                ]);
                
                return response()->json([
                    'success' => false,
                    'needs_activation' => true,
                    'message' => 'Account not activated. Please check your email for the activation link.'
                ], 403);
            }
            $passwordMatches = Hash::check(
                $request->password,
                $user->password_hash
            );
            Log::info('Login: Password check', [
                'user_email' => $user->email,
                'attempted_password' => $request->password,
                'password_hash' => substr($user->password_hash, 0, 20) . '...',
                'match' => $passwordMatches ? 'YES' : 'NO',
            ]);
            if (!$passwordMatches) {
                Log::warning('Login: Password mismatch', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
            if (!$user->is_active) {
                Log::warning('Login: Account disabled', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Account disabled'
                ], 403);
            }
            $user->update([
                'last_login' => now(),
                'activation_status' => 'activated',
            ]);
            $token = $user
                ->createToken('hotel_token')
                ->plainTextToken;
            Log::info('Login: Success', [
                'user_email' => $user->email,
                'token' => substr($token, 0, 20) . '...',
                'timestamp' => now(),
            ]);

            // Ensure memberships are active for an active user
            if ($user->is_active) {
                \App\Models\HotelUser::where('user_id', $user->id)->update(['is_active' => true]);
            }

            $memberships = $user->hotelMemberships()
                ->where('is_active', true)
                ->with('hotel')
                ->get();

            // Enforce hotel suspension for non-platform users
            if (!$user->isPlatformAdmin() && $memberships->isNotEmpty()) {
                $hasActiveHotel = $memberships->contains(fn ($m) => $m->hotel && $m->hotel->isActive());
                if (!$hasActiveHotel) {
                    $firstHotel = $memberships->first()?->hotel;
                    $statusMsg = ($firstHotel && $firstHotel->isSuspended())
                        ? 'Your hotel account has been suspended. Please contact platform administration.'
                        : 'This hotel account is currently inactive or archived. Please contact platform administration.';
                    return response()->json([
                        'success' => false,
                        'message' => $statusMsg,
                    ], 403);
                }
            }

            $defaultHotel = $memberships->first()?->hotel 
                ?: ($user->isPlatformAdmin() ? \App\Models\Hotel::first() : null);

            $hotelList = $memberships->map(fn ($m) => [
                'id' => $m->hotel_id,
                'name' => $m->hotel?->name,
                'slug' => $m->hotel?->slug,
                'logo' => $m->hotel?->logo,
                'role' => $m->role,
                'currency' => $m->hotel?->currency ?? 'ETB',
            ]);

            if ($defaultHotel) {
                app(\App\Services\TenantContext::class)->setHotelId($defaultHotel->id);
                \Illuminate\Support\Facades\Cache::forget("user_permissions_{$defaultHotel->id}_{$user->id}");
                \Illuminate\Support\Facades\Cache::forget("user_permissions_global_{$user->id}");
            }

            $currentHotelPayload = $defaultHotel ? [
                'id' => $defaultHotel->id,
                'name' => $defaultHotel->name,
                'slug' => $defaultHotel->slug,
                'logo' => $defaultHotel->logo,
                'currency' => $defaultHotel->currency ?? 'ETB',
                'role' => $user->getHotelRole($defaultHotel->id) ?? ($user->isPlatformAdmin() ? 'admin' : $user->role),
            ] : null;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => new AuthResource($user),
                'hotels' => $hotelList,
                'current_hotel' => $currentHotelPayload,
            ]);
        } catch (\Exception $exception) {
            Log::error('Login: Exception', [
                'email' => $request->email,
                'error_message' => $exception->getMessage(),
                'error_file' => $exception->getFile(),
                'error_line' => $exception->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Login error. Please try again.'
            ], 500);
        }
    }
    public function me(Request $request)
    {
        $user = $request->user();
        $memberships = $user->hotelMemberships()
            ->where('is_active', true)
            ->with('hotel')
            ->get();

        $hotelId = $request->header('X-Hotel-ID')
            ?: app(\App\Services\TenantContext::class)->getHotelId()
            ?: $user->hotel_id
            ?: $memberships->first()?->hotel_id;

        if ($hotelId) {
            app(\App\Services\TenantContext::class)->setHotelId($hotelId);
        }

        $activeHotel = $hotelId ? \App\Models\Hotel::find($hotelId) : null;
        if (!$activeHotel && $memberships->isNotEmpty()) {
            $activeHotel = $memberships->first()->hotel;
        }

        $currentHotelPayload = $activeHotel ? [
            'id' => $activeHotel->id,
            'name' => $activeHotel->name,
            'slug' => $activeHotel->slug,
            'logo' => $activeHotel->logo,
            'currency' => $activeHotel->currency ?? 'ETB',
            'role' => $user->getHotelRole($activeHotel->id) ?? ($user->isPlatformAdmin() ? 'admin' : $user->role),
        ] : null;

        return response()->json([
            'success' => true,
            'user' => new AuthResource($user),
            'current_hotel' => $currentHotelPayload,
            'hotels' => $memberships->map(fn ($m) => [
                'id' => $m->hotel_id,
                'name' => $m->hotel?->name,
                'slug' => $m->hotel?->slug,
                'logo' => $m->hotel?->logo,
                'role' => $m->role,
                'currency' => $m->hotel?->currency ?? 'ETB',
            ]),
        ]);
    }
    public function switchHotel(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|uuid|exists:hotels,id',
        ]);

        $user = $request->user();
        $hotel = \App\Models\Hotel::findOrFail($validated['hotel_id']);

        if (!$hotel->isActive() && !$user->isPlatformAdmin()) {
            $statusMsg = $hotel->isSuspended()
                ? 'Your hotel account has been suspended. Please contact platform administration.'
                : 'This hotel account is currently inactive or archived. Please contact platform administration.';
            return response()->json([
                'success' => false,
                'message' => $statusMsg,
            ], 403);
        }

        if (!$user->isPlatformAdmin() && !$user->belongsToHotel($hotel->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this hotel.',
            ], 403);
        }

        $hotelId = $hotel->id;
        app(\App\Services\TenantContext::class)->setHotelId($hotelId);

        // Invalidate stale permission cache for this hotel and user
        \Illuminate\Support\Facades\Cache::forget("user_permissions_{$hotelId}_{$user->id}");
        \Illuminate\Support\Facades\Cache::forget("user_permissions_global_{$user->id}");

        $hotelRole = $user->getHotelRole($hotel->id) ?? ($user->isPlatformAdmin() ? 'admin' : $user->role);

        // Fetch effective roles and permissions for the target hotel
        $authService = app(\App\Services\AuthorizationService::class);
        $activeRoles = $authService->getActiveRoles($user, $hotel->id);
        $effectivePermissions = $authService->getEffectivePermissions($user, $hotel->id);

        return response()->json([
            'success' => true,
            'message' => 'Switched hotel successfully',
            'user' => new \App\Http\Resources\AuthResource($user),
            'current_hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'logo' => $hotel->logo,
                'currency' => $hotel->currency,
                'role' => $hotelRole,
            ],
            'roles' => $activeRoles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'is_system' => $r->is_system,
            ])->values()->toArray(),
            'permissions' => $effectivePermissions,
        ]);
    }
    public function myHotels(Request $request)
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            $hotels = \App\Models\Hotel::all();
            return response()->json([
                'success' => true,
                'hotels' => $hotels->map(fn ($h) => [
                    'id' => $h->id,
                    'name' => $h->name,
                    'slug' => $h->slug,
                    'logo' => $h->logo,
                    'role' => 'admin',
                    'currency' => $h->currency,
                ]),
            ]);
        }

        $memberships = $user->hotelMemberships()
            ->where('is_active', true)
            ->with('hotel')
            ->get();

        return response()->json([
            'success' => true,
            'hotels' => $memberships->map(fn ($m) => [
                'id' => $m->hotel_id,
                'name' => $m->hotel?->name,
                'slug' => $m->hotel?->slug,
                'logo' => $m->hotel?->logo,
                'role' => $m->role,
                'currency' => $m->hotel?->currency ?? 'ETB',
            ]),
        ]);
    }
    public function logout(Request $request)
    {
        if ($request->user() && method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Update password for authenticated user (e.g. required after system-generated password)
     */
    public function updatePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password does not match our records.',
            ], 422);
        }

        $user->password_hash = \Illuminate\Support\Facades\Hash::make($validated['new_password']);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully. You can now use your new password.',
            'user' => new AuthResource($user),
        ]);
    }
}
