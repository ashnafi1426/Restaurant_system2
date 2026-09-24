<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'base_price_per_night' => (float) $this->base_price_per_night,
            'capacity' => $this->capacity,
            'amenities' => $this->amenities ?? [],
            'amenities_count' => count($this->amenities ?? []),
            'is_active' => $this->is_active,
            'status' => $this->is_active ? 'Active' : 'Inactive',
            'rooms_count' => $this->whenCounted('rooms'),
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}