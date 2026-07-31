<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerAnnouncementResource extends JsonResource
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

            'title' => $this->title,

            'content' => $this->content,

            'is_active' => $this->is_active,

            'published_at' => $this->published_at,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}