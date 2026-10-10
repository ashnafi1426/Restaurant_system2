<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Guest;
use App\Services\AuthorizationService;
use App\Services\TenantContext;

class GuestPolicy
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
            && $this->authService->hasPermission($user, 'guests.view');
    }

    public function view(User $user, Guest $guest): bool
    {
        return $this->checkTenantAccess($user, $guest->hotel_id)
            && $this->authService->hasPermission($user, 'guests.view');
    }

    public function create(User $user): bool
    {
        return $this->checkTenantAccess($user, null)
            && $this->authService->hasPermission($user, 'guests.create');
    }

    public function update(User $user, Guest $guest): bool
    {
        return $this->checkTenantAccess($user, $guest->hotel_id)
            && $this->authService->hasPermission($user, 'guests.update');
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $this->checkTenantAccess($user, $guest->hotel_id)
            && $this->authService->hasPermission($user, 'guests.delete');
    }
}

