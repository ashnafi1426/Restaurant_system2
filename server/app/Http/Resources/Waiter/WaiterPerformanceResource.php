<?php

namespace App\Http\Resources\Waiter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaiterPerformanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waiter_id' => $this->waiter_id,
            'date' => $this->date->format('Y-m-d'),
            'metrics' => [
                'total_assignments' => $this->total_assignments,
                'accepted_assignments' => $this->accepted_assignments,
                'rejected_assignments' => $this->rejected_assignments,
                'completed_deliveries' => $this->completed_deliveries,
                'failed_deliveries' => $this->failed_deliveries,
            ],
            'performance' => [
                'average_delivery_time' => $this->average_delivery_time,
                'completion_rate' => $this->completion_rate,
                'failure_rate' => round(($this->failed_deliveries / max($this->total_assignments, 1)) * 100, 2),
                'guest_rating' => $this->guest_rating,
                'rating' => $this->rating,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
