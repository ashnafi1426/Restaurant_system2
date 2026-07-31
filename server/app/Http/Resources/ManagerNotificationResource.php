<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerNotificationResource extends JsonResource
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
                'email' => $this->manager?->email,
            ],

            'title' => $this->title,

            'message' => $this->message,

            'type' => $this->type,

            'is_read' => $this->is_read,

            'read_at' => $this->read_at,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}