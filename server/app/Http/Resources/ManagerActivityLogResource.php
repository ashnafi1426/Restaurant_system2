<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'manager' => [
                'id' => $this->manager?->id,
                'name' => $this->manager?->name,
            ],

            'module' => $this->module,

            'action' => $this->action,

            'reference_type' => $this->reference_type,

            'reference_id' => $this->reference_id,

            'description' => $this->description,

            'ip_address' => $this->ip_address,

            'device' => $this->device,

            'created_at' => $this->created_at,

        ];
    }
}