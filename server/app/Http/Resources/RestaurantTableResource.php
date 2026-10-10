<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'hotel_id'        => $this->hotel_id,
            'table_number'    => $this->table_number,
            'table_name'      => $this->table_name,
            'capacity'        => (int) $this->capacity,
            'location'        => $this->location,
            'section_id'      => $this->section_id,
            'section'         => $this->section_name,
            'section_name'    => $this->section_name,
            'status'          => $this->status,
            'is_active'       => (bool) $this->is_active,
            'qr_token'        => $this->qr_token,
            'qr_image_path'   => $this->qr_image_path,
            'qr_code_url'     => $this->qr_code_url,
            'qr_generated_at' => $this->qr_generated_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
            'section_details' => $this->whenLoaded('restaurantSection', function () {
                return [
                    'id'          => $this->restaurantSection->id,
                    'name'        => $this->restaurantSection->name,
                    'description' => $this->restaurantSection->description,
                ];
            }),
        ];
    }
}

