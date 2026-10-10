<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_hotels' => $this->resource['total_hotels'] ?? 0,
            'active_hotels' => $this->resource['active_hotels'] ?? 0,
            'inactive_hotels' => $this->resource['inactive_hotels'] ?? 0,
            'suspended_hotels' => $this->resource['suspended_hotels'] ?? 0,
            'total_users' => $this->resource['total_users'] ?? 0,
            'total_hotel_admins' => $this->resource['total_hotel_admins'] ?? 0,
            'total_staff' => $this->resource['total_staff'] ?? 0,
            'total_guests' => $this->resource['total_guests'] ?? 0,
            'total_rooms' => $this->resource['total_rooms'] ?? 0,
            'total_reservations' => $this->resource['total_reservations'] ?? 0,
            'total_orders' => $this->resource['total_orders'] ?? 0,
            'total_payments' => $this->resource['total_payments'] ?? 0,
            'total_revenue' => (float) ($this->resource['total_revenue'] ?? 0),
            'recent_hotels' => $this->resource['recent_hotels'] ?? [],
            'top_hotels' => $this->resource['top_hotels'] ?? [],
        ];
    }
}

