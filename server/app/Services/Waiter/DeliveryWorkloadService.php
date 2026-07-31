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

            $waiter->incrementOrders();

            Log::info('Delivery Task Assigned', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'waiter_id' => $waiter->id,
                'current_orders' => $waiter->current_orders,
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
