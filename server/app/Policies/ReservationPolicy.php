<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Reservation;
use App\Services\AuthorizationService;

class ReservationPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function viewAny(User $user): bool
    {
        return $this->authService->hasPermission($user, 'reservations.view');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->authService->hasPermission($user, 'reservations.view');
    }

    public function create(User $user): bool
    {
        return $this->authService->hasPermission($user, 'reservations.create');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $this->authService->hasPermission($user, 'reservations.update');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $this->authService->hasPermission($user, 'reservations.cancel');
    }

    public function checkin(User $user, Reservation $reservation): bool
    {
        return $this->authService->hasPermission($user, 'reservations.checkin');
    }

    public function checkout(User $user, Reservation $reservation): bool
    {
        return $this->authService->hasPermission($user, 'reservations.checkout');
    }
}
