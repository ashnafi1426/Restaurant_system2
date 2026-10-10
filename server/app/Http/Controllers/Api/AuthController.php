<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authrequest;
use App\Http\Resources\AuthResource;
use App\Models\Hotel;
use App\Models\HotelUser;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Authenticate user, issue Sanctum token, and establish hotel context.
     */
    public function login(Authrequest $request): JsonResponse
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Login failed: User not found', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => trans_msg('login_failed', default: 'Invalid credentials'),
                ], 401);
            }

            if ($user->needsActivation() && empty($user->password_hash)) {
                Log::warning('Login: Account not activated', [
                    'email' => $request->email,
                    'activation_status' => $user->activation_status,
                ]);

                return response()->json([
                    'success' => false,
                    'needs_activation' => true,
                    'message' => trans_msg('account_not_activated', default: 'Account not activated. Please check your email for the activation link.'),
                ], 403);
            }

            if (!Hash::check($request->password, $user->password_hash)) {
                Log::warning('Login: Password mismatch', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => trans_msg('login_failed', default: 'Invalid credentials'),
                ], 401);
            }

            if (!$user->is_active) {
                Log::warning('Login: Account disabled', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => trans_msg('account_disabled', default: 'Account disabled'),
                ], 403);
            }

            $user->update([
                'last_login' => now(),
                'activation_status' => 'activated',
            ]);

            $token = $user->createToken('hotel_token')->plainTextToken;

            HotelUser::where('user_id', $user->id)->update(['is_active' => true]);

            $memberships = $user->hotelMemberships()
                ->where('is_active', true)
                ->with('hotel')
                ->get();

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
                ?: ($user->isPlatformAdmin() ? Hotel::first() : null);

            $hotelList = $this->formatMemberships($memberships);

            if ($defaultHotel) {
                app(TenantContext::class)->setHotelId($defaultHotel->id);
                Cache::forget("user_permissions_{$defaultHotel->id}_{$user->id}");
                Cache::forget("user_permissions_global_{$user->id}");
            }

            $currentHotelPayload = $defaultHotel ? $this->formatHotelSummary($defaultHotel, $user) : null;

            return response()->json([
                'success' => true,
                'message' => trans_msg('login_success', default: 'Login successful'),
                'token' => $token,
                'user' => new AuthResource($user),
                'hotels' => $hotelList,
                'current_hotel' => $currentHotelPayload,
            ]);
        } catch (Exception $exception) {
            Log::error('Login exception', [
                'email' => $request->email,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login error. Please try again.',
            ], 500);
        }
    }

    /**
     * Get authenticated user profile and active hotel state.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $memberships = $user->hotelMemberships()
            ->where('is_active', true)
            ->with('hotel')
            ->get();

        $hotelId = $request->header('X-Hotel-ID')
            ?: TenantContext::id()
            ?: $user->hotel_id
            ?: $memberships->first()?->hotel_id;

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        $activeHotel = $hotelId ? Hotel::find($hotelId) : null;
        if (!$activeHotel && $memberships->isNotEmpty()) {
            $activeHotel = $memberships->first()->hotel;
        }

        $currentHotelPayload = $activeHotel ? $this->formatHotelSummary($activeHotel, $user) : null;

        return response()->json([
            'success' => true,
            'user' => new AuthResource($user),
            'current_hotel' => $currentHotelPayload,
            'hotels' => $this->formatMemberships($memberships),
        ]);
    }

    /**
     * Switch active hotel workspace for the authenticated user.
     */
    public function switchHotel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hotel_id' => 'required|uuid|exists:hotels,id',
        ]);

        $user = $request->user();
        $hotel = Hotel::findOrFail($validated['hotel_id']);

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
        app(TenantContext::class)->setHotelId($hotelId);

        Cache::forget("user_permissions_{$hotelId}_{$user->id}");
        Cache::forget("user_permissions_global_{$user->id}");

        $authService = app(AuthorizationService::class);
        $activeRoles = $authService->getActiveRoles($user, $hotelId);
        $effectivePermissions = $authService->getEffectivePermissions($user, $hotelId);

        return response()->json([
            'success' => true,
            'message' => trans_msg('hotel_switched', default: 'Switched hotel successfully'),
            'user' => new AuthResource($user),
            'current_hotel' => $this->formatHotelSummary($hotel, $user),
            'roles' => $activeRoles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'is_system' => $r->is_system,
            ])->values()->toArray(),
            'permissions' => $effectivePermissions,
        ]);
    }

    /**
     * Get all hotels accessible to the authenticated user.
     */
    public function myHotels(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            $hotels = Hotel::all();

            return response()->json([
                'success' => true,
                'hotels' => $hotels->map(fn ($h) => [
                    'id' => $h->id,
                    'name' => $h->name,
                    'slug' => $h->slug,
                    'logo' => $h->logo,
                    'role' => 'admin',
                    'currency' => $h->currency ?? 'ETB',
                ]),
            ]);
        }

        $memberships = $user->hotelMemberships()
            ->where('is_active', true)
            ->with('hotel')
            ->get();

        return response()->json([
            'success' => true,
            'hotels' => $this->formatMemberships($memberships),
        ]);
    }

    /**
     * Log out authenticated user by revoking Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user() && method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => trans_msg('logout_success', default: 'Logged out successfully'),
        ]);
    }

    /**
     * Update authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => trans_msg('password_incorrect', default: 'Current password does not match our records.'),
            ], 422);
        }

        $user->password_hash = Hash::make($validated['new_password']);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => trans_msg('password_updated', default: 'Password updated successfully. You can now use your new password.'),
            'user' => new AuthResource($user),
        ]);
    }

    /**
     * Format a hotel model into standard summary array.
     */
    private function formatHotelSummary(Hotel $hotel, User $user): array
    {
        return [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'logo' => $hotel->logo,
            'currency' => $hotel->currency ?? 'ETB',
            'role' => $user->getHotelRole($hotel->id) ?? ($user->isPlatformAdmin() ? 'admin' : $user->role),
        ];
    }

    /**
     * Format hotel membership collection.
     */
    private function formatMemberships($memberships): array
    {
        return $memberships->map(fn ($m) => [
            'id' => $m->hotel_id,
            'name' => $m->hotel?->name,
            'slug' => $m->hotel?->slug,
            'logo' => $m->hotel?->logo,
            'role' => $m->role,
            'currency' => $m->hotel?->currency ?? 'ETB',
        ])->values()->toArray();
    }
}

