<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'is_platform_admin' => $this->is_platform_admin,
            'must_change_password' => $this->must_change_password,
            'activation_status' => $this->activation_status,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'hotels' => $this->when(
                $this->relationLoaded('hotels'),
                function () {
                    return $this->hotels->map(function ($hotel) {
                        return [
                            'id' => $hotel->id,
                            'name' => $hotel->name,
                            'slug' => $hotel->slug,
                            'city' => $hotel->city,
                            'status' => $hotel->status,
                        ];
                    });
                }
            ),
        ];
    }
}

