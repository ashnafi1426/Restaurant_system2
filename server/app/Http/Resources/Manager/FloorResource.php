<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FloorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roomCount = $this->rooms_count ?? ($this->relationLoaded('rooms') ? $this->rooms->count() : ($this->total_rooms ?? 0));

        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'floor_number' => (int) $this->floor_number,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'total_rooms' => (int) $roomCount,
            'room_count' => (int) $roomCount,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            'rooms' => $this->whenLoaded('rooms', function () {
                return $this->rooms->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'room_number' => $room->room_number,
                        'room_type' => $room->roomType?->name ?? 'Standard Room',
                        'status' => $room->status ?? 'available',
                        'is_active' => (bool) ($room->is_active ?? true),
                        'price_per_night' => (float) ($room->price_per_night ?? $room->price ?? 0),
                        'capacity' => (int) ($room->capacity ?? 2),
                    ];
                });
            }),

            'waiter_assignments' => $this->when(
                $this->relationLoaded('waiterAssignments') || $this->relationLoaded('activeWaiterAssignments'),
                function () {
                    $assignments = $this->relationLoaded('waiterAssignments')
                        ? $this->waiterAssignments
                        : $this->activeWaiterAssignments;

                    return $assignments->map(function ($wa) {
                        $user = $wa->waiter?->user;
                        $waiterName = $user
                            ? trim("{$user->first_name} {$user->last_name}")
                            : ($wa->waiter?->name ?? 'Staff Waiter');

                        return [
                            'id' => $wa->id,
                            'waiter_id' => $wa->waiter_id,
                            'waiter_name' => !empty($waiterName) ? $waiterName : ($user?->email ?? 'Waiter'),
                            'first_name' => $user?->first_name,
                            'last_name' => $user?->last_name,
                            'email' => $user?->email,
                            'phone' => $user?->phone,
                            'section' => $wa->waiter?->section,
                            'shift' => $wa->shift ? [
                                'id' => $wa->shift->id,
                                'name' => $wa->shift->name,
                                'start_time' => $wa->shift->start_time,
                                'end_time' => $wa->shift->end_time,
                            ] : null,
                            'priority' => $wa->priority ?? 'primary',
                            'status' => $wa->status ?? 'active',
                            'assignment_date' => $wa->assignment_date,
                            'is_active' => (bool) ($wa->is_active ?? ($wa->status === 'active')),
                        ];
                    });
                }
            ),
        ];
    }
}

