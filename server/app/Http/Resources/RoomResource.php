<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $floorNumber = $this->getAttribute('floor') ?? $this->floor?->floor_number;
        $floorName = $this->floor?->name ?? ($floorNumber !== null ? "Floor {$floorNumber}" : null);

        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'hotel' => $this->whenLoaded('hotel', function () {
                return [
                    'id' => $this->hotel->id,
                    'name' => $this->hotel->name,
                    'city' => $this->hotel->city,
                ];
            }),
            'room_number' => $this->room_number,
            'room_type_id' => $this->room_type_id,
            'floor_id' => $this->floor_id,
            'floor' => $floorNumber,
            'floor_name' => $floorName,
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'status_label' => ucfirst((string) $this->status),
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),
            'qr_token' => $this->qr_token,
            'qr_image_path' => $this->qr_image_path,
            'qr_code_url' => $this->qr_code_url,
            'qr_generated_at' => optional($this->qr_generated_at)->format('Y-m-d H:i:s'),
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

