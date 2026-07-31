<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DeliveryTaskResource
 * 
 * Transforms delivery task data for API responses
 */
class DeliveryTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
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
