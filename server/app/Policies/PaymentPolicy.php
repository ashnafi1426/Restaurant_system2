<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payment;
use App\Services\AuthorizationService;

class PaymentPolicy
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function viewAny(User $user): bool
    {
        return $this->authService->hasPermission($user, 'payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->authService->hasPermission($user, 'payments.view');
    }

    public function create(User $user): bool
    {
        return $this->authService->hasPermission($user, 'payments.create');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $this->authService->hasPermission($user, 'payments.refund');
    }

    public function approveRefund(User $user, Payment $payment): bool
    {
        return $this->authService->hasPermission($user, 'payments.approve_refund');
    }
}
