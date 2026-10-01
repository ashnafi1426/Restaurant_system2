<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaiterFloorAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'waiter' => new WaiterResource($this->waiter),
            'floor' => new FloorResource($this->floor),
            'shift' => new ShiftResource($this->shift),
            'assignment_date' => $this->assignment_date?->format('Y-m-d'),
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'priority' => $this->priority,
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s') ?? $this->created_at?->format('Y-m-d H:i:s'),
            'assigned_by' => [
                'id' => $this->assignedBy?->id,
                'name' => $this->assignedBy?->full_name,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
