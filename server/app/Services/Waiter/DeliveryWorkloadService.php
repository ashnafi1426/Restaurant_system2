<?php

namespace App\Services\Waiter;

use App\Models\Order;
use App\Models\Waiter;
use App\Models\HotelFloor;
use App\Models\DeliveryTask;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliveryWorkloadService
{
    private function resolveSystemAssignedBy(): ?string
    {
        return User::whereIn('role', ['admin', 'administrator', 'manager'])
            ->orderBy('created_at', 'asc')
            ->value('id');
    }

    public function assignDelivery(Order $order, Waiter $waiter, $floor = null): DeliveryTask
    {
        try {
            $hotelId = $order->hotel_id ?? $floor?->hotel_id ?? $waiter->hotel_id ?? app(\App\Services\TenantContext::class)->getHotelId();

            $delivery = DeliveryTask::create([
                'hotel_id'        => $hotelId,
                'order_id'        => $order->id,
                'reservation_id'  => $order->reservation_id,
                'room_id'         => $order->room_id,
                'floor_id'        => $floor?->id,
                'waiter_id'       => $waiter->id,
                'assigned_by'     => $this->resolveSystemAssignedBy(),
                'assignment_type' => 'automatic',
                'status'          => 'accepted',
                'assigned_at'     => now(),
                'accepted_at'     => now(),
            ]);

            $waiter->incrementOrders();
            $waiter->update(['last_assigned_at' => now()]);

            Log::info(' Delivery Task Assigned with tie-breaker update', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->user->name ?? 'Unknown',
                'current_orders' => $waiter->current_orders,
                'last_assigned_at' => now()->toDateTimeString(),
            ]);

            return $delivery;
        } catch (Throwable $e) {
            Log::error('Delivery Assignment Creation Exception', [
                'order_id' => $order->id,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function assignTableDelivery(Order $order, Waiter $waiter, $table): DeliveryTask
    {
        try {
            $hotelId = $order->hotel_id ?? $table?->hotel_id ?? $waiter->hotel_id ?? app(\App\Services\TenantContext::class)->getHotelId();

            $delivery = DeliveryTask::create([
                'hotel_id'        => $hotelId,
                'order_id'        => $order->id,
                'reservation_id'  => $order->reservation_id,
                'table_id'        => $table->id,
                'waiter_id'       => $waiter->id,
                'assigned_by'     => $this->resolveSystemAssignedBy(),
                'assignment_type' => 'automatic',
                'status'          => 'accepted',
                'assigned_at'     => now(),
                'accepted_at'     => now(),
            ]);

            $waiter->incrementOrders();
            $waiter->update(['last_assigned_at' => now()]);

            Log::info(' Table Delivery Task Assigned', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'order_type' => 'walk_in',
                'table_id' => $table->id,
                'table_number' => $table->table_number,
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->user->email ?? 'Unknown',
                'current_orders' => $waiter->current_orders,
                'last_assigned_at' => now()->toDateTimeString(),
            ]);

            return $delivery;
        } catch (Throwable $e) {
            Log::error('Table Delivery Assignment Creation Exception', [
                'order_id' => $order->id,
                'table_id' => $table->id,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function createWaitingDelivery(Order $order, $floor = null, string $reason = 'Waiting assignment'): DeliveryTask
    {
        try {
            $hotelId = $order->hotel_id ?? $floor?->hotel_id ?? app(\App\Services\TenantContext::class)->getHotelId();

            $delivery = DeliveryTask::create([
                'hotel_id'        => $hotelId,
                'order_id'        => $order->id,
                'reservation_id'  => $order->reservation_id,
                'room_id'         => $order->room_id,
                'floor_id'        => $floor?->id,
                'table_id'        => $order->table_id,
                'waiter_id'       => null,
                'assigned_by'     => $this->resolveSystemAssignedBy(),
                'assignment_type' => 'automatic',
                'status'          => 'waiting_assignment',
                'assigned_at'     => null,
                'remarks'         => $reason,
            ]);

            Log::warning('Created Waiting Delivery Task', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'floor_id' => $floor?->id,
                'reason' => $reason,
            ]);

            return $delivery;
        } catch (Throwable $e) {
            Log::error('Waiting Delivery Creation Exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

