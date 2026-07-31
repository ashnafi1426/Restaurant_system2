<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceMetricResource extends JsonResource
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
            'staff' => $this->whenLoaded('staff', function () {
                return [
                    'id' => $this->staff->id,
                    'name' => $this->staff->name,
                    'role' => $this->staff->role,
                ];
            }),
            'department' => $this->department,
            'metric_date' => $this->metric_date->format('Y-m-d'),
            'tasks' => [
                'assigned' => $this->tasks_assigned,
                'completed' => $this->tasks_completed,
                'pending' => $this->tasks_pending,
                'completion_rate' => (float) $this->completion_rate,
            ],
            'timing' => [
                'avg_duration_minutes' => $this->avg_task_duration_minutes,
                'late_tasks' => $this->late_tasks,
                'on_time_rate' => (float) $this->on_time_rate,
            ],
            'quality' => [
                'quality_score' => (float) $this->quality_score,
                'customer_complaints' => $this->customer_complaints,
                'satisfaction_rating' => $this->satisfaction_rating ? (float) $this->satisfaction_rating : null,
            ],
            'department_specific' => [
                'orders_prepared' => $this->orders_prepared,
                'orders_rejected' => $this->orders_rejected,
                'deliveries_completed' => $this->deliveries_completed,
                'deliveries_failed' => $this->deliveries_failed,
                'rooms_cleaned' => $this->rooms_cleaned,
                'inspection_passes' => $this->inspection_passes,
            ],
            'performance_rating' => $this->getPerformanceRating(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
