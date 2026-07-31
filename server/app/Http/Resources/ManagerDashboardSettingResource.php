<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerDashboardSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'manager_id' => $this->manager_id,

            'show_revenue' => $this->show_revenue,

            'show_rooms' => $this->show_rooms,

            'show_orders' => $this->show_orders,

            'show_housekeeping' => $this->show_housekeeping,

            'show_laundry' => $this->show_laundry,

            'show_notifications' => $this->show_notifications,

            'theme' => $this->theme,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}