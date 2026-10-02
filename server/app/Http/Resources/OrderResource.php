<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_type' => $this->payment_type,
            'reservation' => $this->whenLoaded('reservation', function () {
                return [
                    'id' => $this->reservation->id,
                ];
            }),
            'guest' => $this->whenLoaded('guest', function () {
                $fullName = $this->guest->full_name ?? trim(($this->guest->first_name ?? '') . ' ' . ($this->guest->last_name ?? ''));
                return [
                    'id' => $this->guest->id,
                    'first_name' => $this->guest->first_name,
                    'last_name' => $this->guest->last_name,
                    'name' => $fullName ?: ($this->guest->name ?? 'Guest'),
                    'full_name' => $fullName ?: ($this->guest->name ?? 'Guest'),
                    'email' => $this->guest->email,
                    'phone' => $this->guest->phone,
                ];
            }),
            'room' => $this->whenLoaded('room', function () {
                return [
                    'id' => $this->room->id,
                    'room_number' => $this->room->room_number,
                ];
            }),
            'table' => $this->whenLoaded('table', function () {
                return [
                    'id' => $this->table->id,
                    'table_number' => $this->table->table_number,
                ];
            }),
            'order_type' => $this->order_type ?? 'room_service',
            'subtotal' => (float) $this->subtotal,
            'taxable_amount' => (float) ($this->taxable_amount ?? $this->subtotal),
            'tax' => (float) $this->tax,
            'service_charge_rate' => (float) ($this->service_charge_rate ?? 0),
            'service_charge_amount' => (float) ($this->service_charge_amount ?? 0),
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'order_time' => optional($this->order_time)->toDateTimeString(),
            'served_at' => optional($this->served_at)->toDateTimeString(),
            'cancelled_at' => optional($this->cancelled_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'items' => OrderItemResource::collection(
                $this->whenLoaded('orderItems')
            ),
        ];
    }
}