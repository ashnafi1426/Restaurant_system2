<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Reservation;
use App\Services\AuthorizationService;
use App\Services\TenantContext;

class ReservationPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    protected function checkTenantAccess(User $user, ?string $hotelId): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $currentHotelId = $hotelId ?: TenantContext::id();
        return $currentHotelId ? $user->belongsToHotel($currentHotelId) : false;
    }

    public function viewAny(User $user): bool
    {
        return $this->checkTenantAccess($user, null) 
            && $this->authService->hasPermission($user, 'reservations.view');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->checkTenantAccess($user, $reservation->hotel_id) 
            && $this->authService->hasPermission($user, 'reservations.view');
    }

    public function create(User $user): bool
    {
        return $this->checkTenantAccess($user, null) 
            && $this->authService->hasPermission($user, 'reservations.create');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $this->checkTenantAccess($user, $reservation->hotel_id) 
            && $this->authService->hasPermission($user, 'reservations.update');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $this->checkTenantAccess($user, $reservation->hotel_id) 
            && $this->authService->hasPermission($user, 'reservations.cancel');
    }

    public function checkin(User $user, Reservation $reservation): bool
    {
        return $this->checkTenantAccess($user, $reservation->hotel_id) 
            && $this->authService->hasPermission($user, 'reservations.checkin');
    }

    public function checkout(User $user, Reservation $reservation): bool
    {
        return $this->checkTenantAccess($user, $reservation->hotel_id) 
            && $this->authService->hasPermission($user, 'reservations.checkout');
    }
}
