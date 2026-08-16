<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use App\Services\AuthorizationService;

class OrderPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function viewAny(User $user): bool
    {
        return $this->authService->hasPermission($user, 'orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->authService->hasPermission($user, 'orders.view');
    }

    public function create(User $user): bool
    {
        return $this->authService->hasPermission($user, 'orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $this->authService->hasPermission($user, 'orders.update');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->authService->hasPermission($user, 'orders.cancel');
    }

    public function assign(User $user, Order $order): bool
    {
        return $this->authService->hasPermission($user, 'orders.assign');
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->authService->hasPermission($user, 'orders.update_status');
    }
}
