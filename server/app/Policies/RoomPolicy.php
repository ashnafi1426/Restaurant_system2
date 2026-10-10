<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Room;
use App\Services\AuthorizationService;
use App\Services\TenantContext;

class RoomPolicy
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
            && $this->authService->hasPermission($user, 'rooms.view');
    }

    public function view(User $user, Room $room): bool
    {
        return $this->checkTenantAccess($user, $room->hotel_id)
            && $this->authService->hasPermission($user, 'rooms.view');
    }

    public function create(User $user): bool
    {
        return $this->checkTenantAccess($user, null)
            && $this->authService->hasPermission($user, 'rooms.create');
    }

    public function update(User $user, Room $room): bool
    {
        return $this->checkTenantAccess($user, $room->hotel_id)
            && $this->authService->hasPermission($user, 'rooms.update');
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->checkTenantAccess($user, $room->hotel_id)
            && $this->authService->hasPermission($user, 'rooms.delete');
    }

    public function assign(User $user, Room $room): bool
    {
        return $this->checkTenantAccess($user, $room->hotel_id)
            && $this->authService->hasPermission($user, 'rooms.assign');
    }
}

