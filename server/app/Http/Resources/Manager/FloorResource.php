<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Waiter\WaiterResource;

class FloorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'floor_number' => $this->floor_number,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'total_rooms' => $this->rooms_count ?? 0,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
