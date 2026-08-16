<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Room;
use App\Services\AuthorizationService;

class RoomPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function viewAny(User $user): bool
    {
        return $this->authService->hasPermission($user, 'rooms.view');
    }

    public function view(User $user, Room $room): bool
    {
        return $this->authService->hasPermission($user, 'rooms.view');
    }

    public function create(User $user): bool
    {
        return $this->authService->hasPermission($user, 'rooms.create');
    }

    public function update(User $user, Room $room): bool
    {
        return $this->authService->hasPermission($user, 'rooms.update');
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->authService->hasPermission($user, 'rooms.delete');
    }

    public function assign(User $user, Room $room): bool
    {
        return $this->authService->hasPermission($user, 'rooms.assign');
    }
}
