<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MenuItem;
use App\Services\AuthorizationService;
use App\Services\TenantContext;

class MenuItemPolicy
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
            && $this->authService->hasPermission($user, 'menu.view');
    }

    public function view(User $user, MenuItem $item): bool
    {
        return $this->checkTenantAccess($user, $item->hotel_id)
            && $this->authService->hasPermission($user, 'menu.view');
    }

    public function create(User $user): bool
    {
        return $this->checkTenantAccess($user, null)
            && $this->authService->hasPermission($user, 'menu.create');
    }

    public function update(User $user, MenuItem $item): bool
    {
        return $this->checkTenantAccess($user, $item->hotel_id)
            && $this->authService->hasPermission($user, 'menu.update');
    }

    public function delete(User $user, MenuItem $item): bool
    {
        return $this->checkTenantAccess($user, $item->hotel_id)
            && $this->authService->hasPermission($user, 'menu.delete');
    }
}

