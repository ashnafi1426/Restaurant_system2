<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'order_number' => $this->order_number,

            'status' => $this->status,

            'payment_type' => $this->payment_type,

            /*
            |--------------------------------------------------------------------------
            | Reservation
            |--------------------------------------------------------------------------
            */

            'reservation' => $this->whenLoaded('reservation', function () {
                return [
                    'id' => $this->reservation->id,
                ];
            }),

            /*
            |--------------------------------------------------------------------------
            | Guest
            |--------------------------------------------------------------------------
            */

            'guest' => $this->whenLoaded('guest', function () {
                return [
                    'id' => $this->guest->id,
                    'name' => $this->guest->full_name ?? $this->guest->name,
                    'email' => $this->guest->email,
                    'phone' => $this->guest->phone,
                ];
            }),

            /*
            |--------------------------------------------------------------------------
            | Room
            |--------------------------------------------------------------------------
            */

            'room' => $this->whenLoaded('room', function () {
                return [
                    'id' => $this->room->id,
                    'room_number' => $this->room->room_number,
                ];
            }),

            /*
            |--------------------------------------------------------------------------
            | Financial Summary
            |--------------------------------------------------------------------------
            */

            'subtotal' => (float) $this->subtotal,

            'tax' => (float) $this->tax,

            'discount' => (float) $this->discount,

            'total' => (float) $this->total,

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            'order_time' => optional($this->order_time)
                ->toDateTimeString(),

            'served_at' => optional($this->served_at)
                ->toDateTimeString(),

            'cancelled_at' => optional($this->cancelled_at)
                ->toDateTimeString(),

            'created_at' => optional($this->created_at)
                ->toDateTimeString(),

            'updated_at' => optional($this->updated_at)
                ->toDateTimeString(),

            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */

            'items' => OrderItemResource::collection(
                $this->whenLoaded('orderItems')
            ),

        ];
    }
}