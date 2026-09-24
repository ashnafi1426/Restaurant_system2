<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'order_id' => $this->order_id,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'payment_reference' => $this->payment_reference,
            'reservation' => $this->whenLoaded('reservation', function () {
                return [
                    'id' => $this->reservation->id,
                    'booking_reference' => $this->reservation->booking_reference,
                    'guest_id' => $this->reservation->guest_id,
                    'room_id' => $this->reservation->room_id,
                ];
            }),
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'status' => $this->order->status,
                    'subtotal' => (float) $this->order->subtotal,
                    'tax' => (float) $this->order->tax,
                    'discount' => (float) $this->order->discount,
                    'total' => (float) $this->order->total,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}