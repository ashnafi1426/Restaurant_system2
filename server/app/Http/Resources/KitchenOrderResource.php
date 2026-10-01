<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KitchenOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $guestFullName = trim(
            ($this->guest?->first_name ?? '') .
            ' ' .
            ($this->guest?->last_name ?? '')
        );

        if (empty($guestFullName)) {
            $guestFullName = ($this->order_type === 'walk_in' || $this->table_id)
                ? 'Walk-in Guest'
                : 'QR Guest';
        }

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_type' => $this->order_type ?? ($this->table_id ? 'walk_in' : 'room_service'),
            'status' => $this->status,
            'order_time' => optional($this->order_time)?->format('Y-m-d H:i:s'),
            'guest' => [
                'id' => $this->guest?->id,
                'first_name' => $this->guest?->first_name ?? ($this->table_id ? 'Walk-in' : 'QR'),
                'last_name' => $this->guest?->last_name ?? 'Guest',
                'full_name' => $guestFullName,
            ],
            'room' => $this->room ? [
                'id' => $this->room->id,
                'room_number' => $this->room->room_number,
            ] : null,
            'table' => $this->table ? [
                'id' => $this->table->id,
                'table_number' => $this->table->table_number,
                'table_name' => $this->table->table_name ?? ('Table ' . $this->table->table_number),
            ] : null,
            'reservation' => [
                'id' => $this->reservation?->id,
                'booking_reference' => $this->reservation?->booking_reference,
            ],
            'items' => $this->orderItems ? $this->orderItems->map(fn ($item) => [
                'id' => $item->id,
                'menu_item_id' => $item->menu_item_id,
                'name' => $item->menuItem?->name ?? $item->item_name ?? 'Item',
                'category' => is_string($item->menuItem?->category ?? null) 
                    ? $item->menuItem->category 
                    : ($item->menuItem?->categoryRelation?->name ?? 'General'),
                'image' => $item->menuItem?->image_url,
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->item_price_at_order ?? $item->price ?? 0,
                'line_total' => $item->line_total ?? 0,
                'notes' => $item->notes,
            ]) : [],
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}