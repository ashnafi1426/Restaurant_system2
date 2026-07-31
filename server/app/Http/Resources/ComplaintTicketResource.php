<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintTicketResource extends JsonResource
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
            'ticket_number' => $this->ticket_number,
            'type' => $this->type,
            'department' => $this->department,
            'severity' => $this->severity,
            'status' => $this->status,
            'description' => $this->description,
            'resolution_notes' => $this->resolution_notes,
            'satisfaction_rating' => $this->satisfaction_rating,
            'guest' => $this->whenLoaded('guest', function () {
                return [
                    'id' => $this->guest->id,
                    'name' => $this->guest->name,
                    'email' => $this->guest->email,
                ];
            }),
            'manager' => $this->whenLoaded('manager', function () {
                return [
                    'id' => $this->manager->id,
                    'name' => $this->manager->name,
                ];
            }),
            'assigned_to' => $this->whenLoaded('assignedStaff', function () {
                return [
                    'id' => $this->assignedStaff->id,
                    'name' => $this->assignedStaff->name,
                    'role' => $this->assignedStaff->role,
                ];
            }),
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
            'escalated_at' => $this->escalated_at?->format('Y-m-d H:i:s'),
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
