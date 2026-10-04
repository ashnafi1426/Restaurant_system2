<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'logo' => $this->logo,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Additional computed fields when available
            'rooms_count' => $this->when(isset($this->rooms_count), $this->rooms_count),
            'reservations_count' => $this->when(isset($this->reservations_count), $this->reservations_count),
            'guests_count' => $this->when(isset($this->guests_count), $this->guests_count),
            'orders_count' => $this->when(isset($this->orders_count), $this->orders_count),
            'staff_count' => $this->when(isset($this->staff_count), $this->staff_count),
            'revenue_total' => $this->when(isset($this->revenue_total), (float) $this->revenue_total),
            'admin_name' => $this->when(isset($this->admin_name), $this->admin_name),
            'admin_email' => $this->when(isset($this->admin_email), $this->admin_email),
            
            // Relationships - avoid circular references
            'admins' => $this->when(
                $this->relationLoaded('admins'),
                function () {
                    return $this->admins->map(function ($admin) {
                        return [
                            'membership_id' => $admin->id,
                            'user_id' => $admin->user_id,
                            'name' => $admin->user ? ($admin->user->first_name . ' ' . $admin->user->last_name) : 'Unknown',
                            'email' => $admin->user?->email,
                            'phone' => $admin->user?->phone,
                            'role' => $admin->role,
                            'is_active' => $admin->is_active,
                        ];
                    });
                }
            ),
        ];
    }
}