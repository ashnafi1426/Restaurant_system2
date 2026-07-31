<?php

namespace App\Http\Resources\Waiter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryLogResource extends JsonResource
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
            'room_id' => $this->room_id,
            'action' => $this->action,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'order' => [
                'id' => $this->order?->id,
                'order_number' => $this->order?->order_number,
                'room_number' => $this->order?->room_number,
            ],
        ];
    }
}
