<?php

namespace App\Http\Resources\Waiter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaiterProfileResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'waiter' => $this->waiter ? [
                'id' => $this->waiter->id,
                'employee_code' => $this->waiter->employee_code,
                'manager_id' => $this->waiter->manager_id,
                'phone' => $this->waiter->phone,
                'shift' => $this->waiter->shift,
                'status' => $this->waiter->status,
                'created_at' => $this->waiter->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->waiter->updated_at->format('Y-m-d H:i:s'),
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
