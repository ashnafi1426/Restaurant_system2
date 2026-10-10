<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
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
            'hotel_id' => $this->hotel_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'rate' => (float) $this->rate,
            'formatted_rate' => $this->rate . '%',
            'applies_to' => $this->applies_to,
            'is_active' => (bool) $this->is_active,
            'is_default' => (bool) $this->is_default,
            'description' => $this->description,
            'menu_items_count' => $this->whenCounted('menuItems'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

