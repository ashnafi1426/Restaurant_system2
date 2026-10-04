<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight Room Resource for List Views
 * 
 * This resource is optimized for performance by including only
 * the essential fields needed for room list displays, reducing
 * JSON payload size and improving load times.
 */
class RoomListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $floorNumber = $this->getAttribute('floor') ?? $this->floor?->floor_number;

        return [
            'id' => $this->id,
            'room_number' => $this->room_number,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'floor' => $floorNumber,
            'floor_name' => $this->whenLoaded('floor', function () {
                return $this->floor?->name ?? "Floor {$this->floor->floor_number}";
            }),
            'room_type' => [
                'id' => $this->whenLoaded('roomType', fn() => $this->roomType->id),
                'name' => $this->whenLoaded('roomType', fn() => $this->roomType->name),
                'capacity' => $this->whenLoaded('roomType', fn() => $this->roomType->capacity),
                'base_price_per_night' => $this->whenLoaded('roomType', fn() => $this->roomType->base_price_per_night),
            ],
            // Only include QR code URL, not the full path or token
            'qr_code_url' => $this->qr_code_url,
        ];
    }
}
