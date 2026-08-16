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
    /**
     * Resolve the UUID of the first administrator/manager user.
     * Used as the 'assigned_by' actor for automatic system assignments.
     */
    private function resolveSystemAssignedBy(): ?string
    {
        return User::whereIn('role', ['admin', 'administrator', 'manager'])
            ->orderBy('created_at', 'asc')
            ->value('id');
    }
    /**
     * Safely create the DeliveryTask and increment waiter capacity.
     * Assumes it's being called within a database transaction.
     * 
     * IMPORTANT: Automatic assignments are created with status='accepted'
     * This means they immediately appear in waiter's "Ready for Pickup" page
     * The system has already verified the waiter is available and best fit
     * No need for waiter to explicitly accept automatic system assignments
     */
    public function assignDelivery(Order $order, Waiter $waiter, ?HotelFloor $floor): DeliveryTask
    {
        try {
            $delivery = DeliveryTask::create([
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

            // Atomically increment orders and update last_assigned_at
            $waiter->incrementOrders();
            $waiter->update(['last_assigned_at' => now()]);

            Log::info('✅ Delivery Task Assigned with tie-breaker update', [
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

    /**
     * Assign delivery for walk-in table orders
     * Same as assignDelivery but includes table_id
     */
    public function assignTableDelivery(Order $order, Waiter $waiter, $table): DeliveryTask
    {
        try {
            $delivery = DeliveryTask::create([
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

            // Atomically increment orders and update last_assigned_at
            $waiter->incrementOrders();
            $waiter->update(['last_assigned_at' => now()]);

            Log::info('✅ Table Delivery Task Assigned', [
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

    /**
     * Create a pending delivery when no waiter is available.
     */
    public function createWaitingDelivery(Order $order, ?HotelFloor $floor, string $reason): DeliveryTask
    {
        try {
            $delivery = DeliveryTask::create([
                'order_id'        => $order->id,
                'reservation_id'  => $order->reservation_id,
                'room_id'         => $order->room_id,
                'floor_id'        => $floor?->id,
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
