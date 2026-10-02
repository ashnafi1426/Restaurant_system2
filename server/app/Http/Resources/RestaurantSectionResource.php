<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'hotel_id'     => $this->hotel_id,
            'name'         => $this->name,
            'description'  => $this->description,
            'is_active'    => (bool) $this->is_active,
            'tables_count' => $this->tables_count ?? $this->tables()->count(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
