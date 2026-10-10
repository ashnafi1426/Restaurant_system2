<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Hotel;
use App\Models\HotelUser;
use App\Services\TenantContext;

class IdentifyTenant
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $hotelId = $request->header('X-Hotel-ID')
            ?: $request->header('x-hotel-id')
            ?: $request->query('hotel_id')
            ?: $request->input('hotel_id');
        $user = $request->user();

        if (!$user && ($token = $request->bearerToken())) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
                $request->setUserResolver(fn () => $user);
            }
        }

        $isPublicRead = $request->isMethod('GET') && $request->is(
            'api/rooms',
            'api/rooms/*',
            'api/room-types',
            'api/room-types/*',
            'api/floors',
            'api/floors/*',
            'api/reservations/availability',
            'api/qr-codes/*'
        );

        if ($isPublicRead || $request->is(
            'api/login',
            'api/register',
            'api/forgot-password',
            'api/reset-password',
            'api/activation/*',
            'api/public/*',
            'api/permissions',
            'api/permissions/*',
            'api/notifications',
            'api/notifications/*',
            'api/me',
            'api/auth/*',
            'api/guest/*',
            'api/payments/*',
            'api/reservation-payments/*',
            'api/order-payments/*',
            'api/walk-in-payments/*'
        )) {
            if ($hotelId) {
                $hotel = Hotel::find($hotelId);
                if ($hotel) {
                    $membership = $user ? HotelUser::where('hotel_id', $hotelId)->where('user_id', $user->id)->first() : null;
                    $this->tenantContext->setHotel($hotel, $membership);
                }
            } elseif ($user) {
                $membership = $user->hotelMemberships()->where('is_active', true)->first();
                if ($membership && $membership->hotel) {
                    $this->tenantContext->setHotel($membership->hotel, $membership);
                }
            }
            return $next($request);
        }

        if (!$user) {
            if ($hotelId) {
                $hotel = Hotel::find($hotelId);
                if ($hotel && $hotel->isActive()) {
                    $this->tenantContext->setHotel($hotel, null);
                }
            }
            return $next($request);
        }

        if ($user->isPlatformAdmin()) {
            if ($hotelId) {
                $hotel = Hotel::find($hotelId);
                if ($hotel) {
                    $membership = HotelUser::where('hotel_id', $hotelId)->where('user_id', $user->id)->first();
                    $this->tenantContext->setHotel($hotel, $membership);
                }
            }
            return $next($request);
        }

        if ($hotelId) {
            $hotel = Hotel::find($hotelId);

            if (!$hotel) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Hotel not found.',
                ], 404);
            }

            if (!$hotel->isActive() && !$user->isPlatformAdmin()) {
                $statusMsg = $hotel->isSuspended()
                    ? 'Your hotel account has been suspended. Please contact platform administration.'
                    : 'This hotel account is currently inactive or archived. Please contact platform administration.';
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => $statusMsg,
                ], 403);
            }

            if (!$user->isPlatformAdmin()) {
                $membership = HotelUser::where('hotel_id', $hotelId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($membership) {
                    if (!$membership->is_active && $user->is_active) {
                        $membership->update(['is_active' => true]);
                    }
                } else {

                    $userPrimaryMembership = $user->hotelMemberships()->where('is_active', true)->first()
                        ?: $user->hotelMemberships()->first();

                    if ($userPrimaryMembership && $userPrimaryMembership->hotel) {
                        if (!$userPrimaryMembership->is_active && $user->is_active) {
                            $userPrimaryMembership->update(['is_active' => true]);
                        }
                        $hotel = $userPrimaryMembership->hotel;
                        $membership = $userPrimaryMembership;
                    } elseif ($user->isAdmin() || strtolower($user->role ?? '') === 'admin') {

                        $membership = HotelUser::firstOrCreate([
                            'hotel_id' => $hotelId,
                            'user_id' => $user->id,
                        ], [
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'role' => 'admin',
                            'is_active' => true,
                        ]);
                    } else {
                        return response()->json([
                            'error' => 'Forbidden',
                            'message' => 'You do not have access to this hotel.',
                        ], 403);
                    }
                }

                $this->tenantContext->setHotel($hotel, $membership);
            } else {

                $membership = HotelUser::where('hotel_id', $hotelId)
                    ->where('user_id', $user->id)
                    ->first();

                $this->tenantContext->setHotel($hotel, $membership);
            }

            return $next($request);
        }

        $memberships = $user->hotelMemberships()
            ->where('is_active', true)
            ->with('hotel')
            ->get();
        if ($memberships->count() === 1) {
            $membership = $memberships->first();
            $hotel = $membership->hotel;

            if ($hotel && ($hotel->isActive() || $user->isPlatformAdmin())) {
                $this->tenantContext->setHotel($hotel, $membership);
                return $next($request);
            }

            if ($hotel && !$hotel->isActive() && !$user->isPlatformAdmin()) {
                $statusMsg = $hotel->isSuspended()
                    ? 'Your hotel account has been suspended. Please contact platform administration.'
                    : 'This hotel account is currently inactive or archived. Please contact platform administration.';
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => $statusMsg,
                ], 403);
            }
        }

        if ($memberships->count() > 1) {
            $firstActive = $memberships->first(fn ($m) => $m->hotel && $m->hotel->isActive());
            if ($firstActive) {
                $this->tenantContext->setHotel($firstActive->hotel, $firstActive);
                return $next($request);
            }
        }

        if ($user->isPlatformAdmin() || $user->isAdmin() || strtolower($user->role ?? '') === 'admin') {
            $anyHotel = Hotel::where('status', 'active')->first();
            if ($anyHotel) {
                $membership = HotelUser::firstOrCreate([
                    'hotel_id' => $anyHotel->id,
                    'user_id' => $user->id,
                ], [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'role' => 'admin',
                    'is_active' => true,
                ]);
                $this->tenantContext->setHotel($anyHotel, $membership);
            }
            return $next($request);
        }

        return response()->json([
            'error' => 'Forbidden',
            'message' => 'You do not have access to any active hotel.',
        ], 403);
    }
}

