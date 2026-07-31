<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerReportResource extends JsonResource
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

            'report_name' => $this->report_name,

            'report_type' => $this->report_type,

            'from_date' => $this->from_date,

            'to_date' => $this->to_date,

            'file_path' => $this->file_path,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}