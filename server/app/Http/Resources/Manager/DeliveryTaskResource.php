<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'reservation_id' => $this->reservation_id,
            'room_id' => $this->room_id,
            'floor_id' => $this->floor_id,
            'waiter' => new WaiterResource($this->waiter),
            'floor' => new FloorResource($this->floor),
            'room' => $this->room ? [
                'id' => $this->room->id,
                'room_number' => $this->room->room_number,
                'room_type' => $this->room->room_type ?? null,
            ] : ($this->order && $this->order->room ? [
                'id' => $this->order->room->id,
                'room_number' => $this->order->room->room_number,
                'room_type' => $this->order->room->room_type ?? null,
            ] : ($this->order && isset($this->order->room_number) && $this->order->room_number !== '' ? [
                'id' => null,
                'room_number' => $this->order->room_number,
                'room_type' => null,
            ] : null)),
            'assigned_by' => [
                'id' => $this->assignedBy?->id,
                'name' => $this->assignedBy?->full_name,
            ],
            'assignment_type' => $this->assignment_type,
            'status' => $this->status,
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
            'accepted_at' => $this->accepted_at?->format('Y-m-d H:i:s'),
            'picked_up_at' => $this->picked_up_at?->format('Y-m-d H:i:s'),
            'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,
            'delivery_notes' => $this->delivery_notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

