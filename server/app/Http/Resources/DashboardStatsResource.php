<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reception' => [
                'total_reservations' => $this['reception']['total_reservations'] ?? 0,
                'today_reservations' => $this['reception']['today_reservations'] ?? 0,
                'today_check_ins' => $this['reception']['today_check_ins'] ?? 0,
                'today_check_outs' => $this['reception']['today_check_outs'] ?? 0,
                'available_rooms' => $this['reception']['available_rooms'] ?? 0,
                'occupied_rooms' => $this['reception']['occupied_rooms'] ?? 0,
                'total_rooms' => $this['reception']['total_rooms'] ?? 0,
            ],
            'occupancy' => [
                'total_rooms' => $this['occupancy']['total_rooms'] ?? 0,
                'occupied_rooms' => $this['occupancy']['occupied_rooms'] ?? 0,
                'available_rooms' => $this['occupancy']['available_rooms'] ?? 0,
                'occupancy_rate' => $this['occupancy']['occupancy_rate'] ?? 0,
                'checked_in_guests' => $this['occupancy']['checked_in_guests'] ?? 0,
                'checked_out_guests' => $this['occupancy']['checked_out_guests'] ?? 0,
            ],
            'revenue' => [
                'daily_revenue' => $this['revenue']['daily_revenue'] ?? 0,
                'weekly_revenue' => $this['revenue']['weekly_revenue'] ?? 0,
                'monthly_revenue' => $this['revenue']['monthly_revenue'] ?? 0,
                'pending_payments' => $this['revenue']['pending_payments'] ?? 0,
            ],
            'orders' => [
                'total_orders' => $this['orders']['total_orders'] ?? 0,
                'completed_orders' => $this['orders']['completed_orders'] ?? 0,
                'pending_orders' => $this['orders']['pending_orders'] ?? 0,
                'cancelled_orders' => $this['orders']['cancelled_orders'] ?? 0,
                'delivery_in_progress' => $this['orders']['delivery_in_progress'] ?? 0,
            ],
            'kitchen' => [
                'total_orders' => $this['kitchen']['total_orders'] ?? 0,
                'ready_orders' => $this['kitchen']['ready_orders'] ?? 0,
                'preparing_orders' => $this['kitchen']['preparing_orders'] ?? 0,
                'delayed_orders' => $this['kitchen']['delayed_orders'] ?? 0,
            ],
            'waiters' => [
                'total_waiters' => $this['waiters']['total_waiters'] ?? 0,
                'active_waiters' => $this['waiters']['active_waiters'] ?? 0,
                'on_break_waiters' => $this['waiters']['on_break_waiters'] ?? 0,
                'inactive_waiters' => $this['waiters']['inactive_waiters'] ?? 0,
                'total_deliveries_today' => $this['waiters']['total_deliveries_today'] ?? 0,
            ],
            'housekeeping' => [
                'dirty_rooms' => $this['housekeeping']['dirty_rooms'] ?? 0,
                'cleaning_in_progress' => $this['housekeeping']['cleaning_in_progress'] ?? 0,
                'clean_rooms' => $this['housekeeping']['clean_rooms'] ?? 0,
                'maintenance_required' => $this['housekeeping']['maintenance_required'] ?? 0,
            ],
            'complaints' => [
                'total_complaints' => $this['complaints']['total_complaints'] ?? 0,
                'open_complaints' => $this['complaints']['open_complaints'] ?? 0,
                'urgent_complaints' => $this['complaints']['urgent_complaints'] ?? 0,
                'resolved_complaints' => $this['complaints']['resolved_complaints'] ?? 0,
            ],
            'staff' => [
                'total_staff' => $this['staff']['total_staff'] ?? 0,
                'on_duty' => $this['staff']['on_duty'] ?? 0,
            ],
        ];
    }
}
