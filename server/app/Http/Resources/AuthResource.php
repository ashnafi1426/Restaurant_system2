<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hotelId = app(\App\Services\TenantContext::class)->getHotelId()
            ?: $request->header('X-Hotel-ID')
            ?: $this->hotel_id
            ?: $this->hotelMemberships()->where('is_active', true)->value('hotel_id');

        $authService = app(\App\Services\AuthorizationService::class);

        $activeRoles = $authService->getActiveRoles($this->resource, $hotelId);
        $effectivePermissions = $authService->getEffectivePermissions($this->resource, $hotelId);
        $tempAssignments = $authService->getActiveTemporaryRoles($this->resource);

        $primaryRoleSlug = $activeRoles->isNotEmpty()
            ? strtolower($activeRoles->first()->slug)
            : ($this->isPlatformAdmin() ? 'admin' : strtolower($this->role ?? 'guest'));

        return [
            'id' => $this->id,
            'full_name' => "{$this->first_name} {$this->last_name}",
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $primaryRoleSlug,
            'is_active' => $this->is_active,
            'must_change_password' => (bool) ($this->must_change_password ?? false),
            'is_platform_admin' => (bool) ($this->is_platform_admin ?? false),
            'last_login' => $this->last_login,
            'created_at' => $this->created_at,
            'roles' => $activeRoles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'is_system' => $r->is_system,
            ])->values()->toArray(),
            'permissions' => $effectivePermissions,
            'temporary_roles' => [],
        ];
    }
}
