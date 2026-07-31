<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * WaiterFloorAssignmentResource
 * 
 * Transforms floor assignment data for API responses
 */
class WaiterFloorAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waiter' => new WaiterResource($this->waiter),
            'floor' => new FloorResource($this->floor),
            'shift' => new ShiftResource($this->shift),
            'assignment_date' => $this->assignment_date?->format('Y-m-d'),
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_by' => [
                'id' => $this->assignedBy?->id,
                'name' => $this->assignedBy?->full_name,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
