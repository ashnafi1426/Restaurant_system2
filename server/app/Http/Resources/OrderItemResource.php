<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'menu_item_id' => $this->menu_item_id,

            'menu_item_name' => $this->whenLoaded(
                'menuItem',
                fn () => $this->menuItem->name
            ),

            'menu_item_image' => $this->whenLoaded(
                'menuItem',
                fn () => $this->menuItem->image
            ),

            'quantity' => (int) $this->quantity,

            'item_price' => (float) $this->item_price_at_order,

            'line_total' => (float) $this->line_total,

            'notes' => $this->notes,

            'created_at' => optional($this->created_at)
                ->toDateTimeString(),
        ];
    }
}