<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'membership_id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->when(
                $this->relationLoaded('user') && $this->user, 
                fn() => $this->user->first_name . ' ' . $this->user->last_name,
                'Unknown'
            ),
            'email' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn() => $this->user->email
            ),
            'phone' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn() => $this->user->phone
            ),
            'role' => $this->role,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Include hotel info when available
            'hotel' => $this->whenLoaded('hotel', function () {
                return [
                    'id' => $this->hotel->id,
                    'name' => $this->hotel->name,
                    'slug' => $this->hotel->slug,
                ];
            }),
        ];
    }
}