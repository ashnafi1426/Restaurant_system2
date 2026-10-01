<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'hotel' => $this->relationLoaded('hotel') && $this->hotel ? [
                'id' => $this->hotel->id,
                'name' => $this->hotel->name,
                'city' => $this->hotel->city,
            ] : ($this->hotel ? [
                'id' => $this->hotel->id,
                'name' => $this->hotel->name,
                'city' => $this->hotel->city,
            ] : null),
            'room_number' => $this->room_number,
            'room_type_id' => $this->room_type_id,
            'floor_id' => $this->floor_id,
            'floor' => $this->floor,
            'floor_name' => $this->floorRelation?->name ?? ($this->hotelFloor?->name ?? ($this->floor ? "Floor {$this->floor}" : null)),
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'status_label' => ucfirst($this->status),
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