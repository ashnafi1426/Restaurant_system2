<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $imageUrl = null;
        
        // If image exists, convert to full URL
        if ($this->image) {
            // Check if it's already a full URL (external image)
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                $imageUrl = $this->image;
            } else {
                // Convert local path to full URL
                $imageUrl = asset('storage/' . $this->image);
            }
        }
        
        return [

            'id' => $this->id,

            'name' => $this->name,

            'description' => $this->description,

            'category' => $this->category,

            'price' => (float) $this->price,

            'formatted_price' => number_format($this->price, 2),

            'image' => $imageUrl,

            'image_url' => $imageUrl,

            'is_available' => $this->is_available,

            'status' => $this->is_available
                ? 'Available'
                : 'Unavailable',

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('Y-m-d H:i:s'),

        ];
    }
}