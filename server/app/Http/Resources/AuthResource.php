<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $authService = app(\App\Services\AuthorizationService::class);

        $activeRoles = $authService->getActiveRoles($this->resource);
        $effectivePermissions = $authService->getEffectivePermissions($this->resource);
        $tempAssignments = $authService->getActiveTemporaryRoles($this->resource);

        return [
            'id' => $this->id,
            'full_name' => "{$this->first_name} {$this->last_name}",
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => strtolower($this->role ?? 'guest'),
            'is_active' => $this->is_active,
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
