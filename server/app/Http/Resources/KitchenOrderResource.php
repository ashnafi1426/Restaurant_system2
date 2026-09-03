<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KitchenOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Order Information
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'order_number' => $this->order_number,

            'status' => $this->status,

            'order_time' => optional($this->order_time)
                ?->format('Y-m-d H:i:s'),

            /*
            |--------------------------------------------------------------------------
            | Guest
            |--------------------------------------------------------------------------
            */

            'guest' => [

                'id' => $this->guest?->id,

                'first_name' => $this->guest?->first_name,

                'last_name' => $this->guest?->last_name,

                'full_name' => trim(
                    ($this->guest?->first_name ?? '') .
                    ' ' .
                    ($this->guest?->last_name ?? '')
                ),

            ],

            /*
            |--------------------------------------------------------------------------
            | Room
            |--------------------------------------------------------------------------
            */

            'room' => [

                'id' => $this->room?->id,

                'room_number' => $this->room?->room_number,

            ],

            /*
            |--------------------------------------------------------------------------
            | Reservation
            |--------------------------------------------------------------------------
            */

            'reservation' => [

                'id' => $this->reservation?->id,

                'booking_reference' => $this->reservation?->booking_reference,

            ],

            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'subtotal' => $this->subtotal,

            'tax' => $this->tax,

            'discount' => $this->discount,

            'total' => $this->total,

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' => optional($this->created_at)
                ?->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ?->format('Y-m-d H:i:s'),

        ];
    }
}