<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FloorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roomCount = $this->rooms_count ?? $this->total_rooms ?? 0;

        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'floor_number' => (int) $this->floor_number,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'total_rooms' => (int) $roomCount,
            'room_count' => (int) $roomCount,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
