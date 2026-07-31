<?php

namespace App\Http\Resources\Waiter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaiterAssignmentResource extends JsonResource
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
            'order_id' => $this->order_id,
            'assigned_by' => $this->assigned_by,
            'assigned_at' => $this->assigned_at,
            'accepted_at' => $this->accepted_at,
            'rejected_at' => $this->cancelled_at, // Map to cancelled_at for frontend compatibility
            'picked_up_at' => $this->picked_up_at,
            'delivered_at' => $this->delivered_at,
            'failed_at' => $this->cancelled_at, // Map to cancelled_at for frontend compatibility
            'status' => $this->status,
            'rejection_reason' => $this->cancellation_reason,
            'failure_reason' => $this->cancellation_reason,
            'remarks' => $this->remarks,
            'delivery_time_minutes' => $this->getDeliveryDurationMinutes(),
            'order' => [
                'id' => $this->order?->id,
                'order_number' => $this->order?->order_number,
                'room_number' => $this->order?->room_number,
                'guest_name' => $this->order?->guest?->name,
                'priority' => $this->order?->priority,
                'items_count' => $this->order?->orderItems?->count() ?? 0,
            ],
            'waiter' => [
                'id' => $this->waiter?->id,
                'name' => $this->waiter?->name,
                'employee_code' => $this->waiter?->employee_code ?? null,
            ],
            'assigned_by_user' => [
                'id' => $this->assignedBy?->id,
                'name' => $this->assignedBy?->name,
            ],
        ];
    }
}
