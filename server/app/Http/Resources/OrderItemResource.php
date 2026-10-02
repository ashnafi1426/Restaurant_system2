<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'item_name' => $this->item_name ?: ($this->relationLoaded('menuItem') ? $this->menuItem?->name : ($this->item_name ?? 'Menu Item')),
            'menu_item_name' => $this->item_name ?: ($this->relationLoaded('menuItem') ? $this->menuItem?->name : ($this->item_name ?? 'Menu Item')),
            'menu_item_image' => $this->whenLoaded(
                'menuItem',
                fn () => $this->menuItem->image
            ),
            'quantity' => (int) $this->quantity,
            'item_price' => (float) $this->item_price_at_order,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_rate' => (float) ($this->tax_rate ?? 0),
            'tax_amount' => (float) ($this->tax_amount ?? 0),
            'subtotal' => (float) ($this->subtotal ?? $this->line_total),
            'total' => (float) ($this->total ?? $this->line_total),
            'line_total' => (float) $this->line_total,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}