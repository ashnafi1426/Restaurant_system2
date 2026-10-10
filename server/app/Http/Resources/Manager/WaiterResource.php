<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaiterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->full_name ?: ($this->user?->name ?: ($this->user?->email ?: "Waiter #{$this->id}")),
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
            ],
            'name' => $this->user?->full_name ?: ($this->user?->name ?: ($this->user?->email ?: "Waiter #{$this->id}")),
            'employee_number' => $this->employee_number,
            'phone' => $this->phone,
            'employment_type' => $this->employment_type,
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'status' => $this->status,
            'availability' => $this->availability,
            'current_orders' => $this->current_orders,
            'maximum_orders' => $this->maximum_orders,
            'profile_photo' => $this->profile_photo,
            'is_busy' => $this->current_orders >= $this->maximum_orders,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

