<?php

namespace App\Services;

use App\Events\OrderReadyEvent;
use App\Models\DeliveryTask;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Models\Waiter;
use App\Models\WaiterNotification;
use App\Services\RestaurantChargeService;
use App\Services\TenantContext;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use App\Services\Waiter\WaiterNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderStatusService
{
    public const VALID_STATUSES = [
        Order::STATUS_PENDING,
        Order::STATUS_PREPARING,
        Order::STATUS_READY,
        Order::STATUS_SERVED,
        Order::STATUS_CANCELLED,
    ];

    /**
     * Allowed status transitions state machine.
     * Prevents skipping business-critical statuses:
     * pending -> preparing -> ready -> served
     * Any non-terminal state can be cancelled.
     */
    protected array $allowedTransitions = [
        Order::STATUS_PENDING => [
            Order::STATUS_PREPARING,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PREPARING => [
            Order::STATUS_READY,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_READY => [
            Order::STATUS_SERVED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_SERVED => [],
        Order::STATUS_CANCELLED => [],
    ];

    public function __construct(
        protected RestaurantChargeService $restaurantChargeService,
        protected WaiterNotificationService $waiterNotificationService,
        protected AutomaticWaiterAssignmentService $waiterAssignmentService
    ) {}

    /**
     * Validate whether a status transition is permitted.
     */
    public function canTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return true;
        }

        $allowed = $this->allowedTransitions[$currentStatus] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Transition an order to a target status with full validation, history, and notifications.
     */
    public function transition(Order $order, string $newStatus, array $extra = [], ?User $actor = null): Order
    {
        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid order status: '{$newStatus}'.");
        }

        if ($order->status === $newStatus) {
            return $this->loadOrderRelations($order);
        }

        if (!$this->canTransition($order->status, $newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition order #{$order->order_number} from status '{$order->status}' to '{$newStatus}'."
            );
        }

        return DB::transaction(function () use ($order, $newStatus, $extra, $actor) {
            switch ($newStatus) {
                case Order::STATUS_PREPARING:
                    return $this->handlePreparing($order, $actor);

                case Order::STATUS_READY:
                    return $this->handleReady($order);

                case Order::STATUS_SERVED:
                    return $this->handleServed($order);

                case Order::STATUS_CANCELLED:
                    return $this->handleCancelled($order, $extra['reason'] ?? null);

                default:
                    throw new InvalidArgumentException("Unhandled transition target '{$newStatus}'.");
            }
        });
    }

    /**
     * Start preparation on an order.
     */
    public function startPreparing(Order $order, ?User $actor = null): Order
    {
        return $this->transition($order, Order::STATUS_PREPARING, [], $actor);
    }

    /**
     * Mark an order as ready and notify the assigned waiter.
     */
    public function markReady(Order $order): Order
    {
        return $this->transition($order, Order::STATUS_READY);
    }

    /**
     * Mark an order as served / completed.
     */
    public function markServed(Order $order): Order
    {
        return $this->transition($order, Order::STATUS_SERVED);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order, ?string $reason = null): Order
    {
        return $this->transition($order, Order::STATUS_CANCELLED, ['reason' => $reason]);
    }

    /**
     * Handle transition to PREPARING.
     */
    protected function handlePreparing(Order $order, ?User $actor = null): Order
    {
        $updateData = ['status' => Order::STATUS_PREPARING];

        // If a chef initiated and order has no chef_id yet, assign them
        if (!$order->chef_id && $actor && in_array(strtolower($actor->role ?? ''), ['chef', 'cook', 'kitchen_staff'])) {
            $updateData['chef_id'] = $actor->id;
        }

        $order->update($updateData);

        return $this->loadOrderRelations($order->fresh());
    }

    /**
     * Handle transition to READY.
     * Updates status, dispatches OrderReadyEvent, and notifies the assigned waiter.
     */
    protected function handleReady(Order $order): Order
    {
        $order->update(['status' => Order::STATUS_READY]);
        $freshOrder = $this->loadOrderRelations($order->fresh());

        // 1. Notify or assign waiter
        $this->notifyAssignedWaiterOnReady($freshOrder);

        // 2. Dispatch OrderReadyEvent for external/broadcast listeners
        try {
            OrderReadyEvent::dispatch($freshOrder);
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch OrderReadyEvent for order #{$order->id}: {$e->getMessage()}");
        }

        // 3. Notify kitchen staff / chefs
        $this->notifyChefs(
            'order_ready',
            'Order Ready for Pickup',
            "Order #{$order->order_number} is ready for pickup/delivery.",
            $freshOrder
        );

        return $freshOrder;
    }

    /**
     * Handle transition to SERVED.
     * Updates status, logs served_at, marks delivery task delivered, charges room if applicable.
     */
    protected function handleServed(Order $order): Order
    {
        $order->update([
            'status' => Order::STATUS_SERVED,
            'served_at' => now(),
        ]);

        // Release restaurant table back to available
        if ($order->table_id && $order->table) {
            try {
                $order->table->update(['status' => \App\Models\RestaurantTable::STATUS_AVAILABLE]);
            } catch (\Throwable $te) {
                Log::warning("Failed to reset table status upon serving order #{$order->id}: {$te->getMessage()}");
            }
        }

        // Complete delivery task if active
        try {
            DeliveryTask::withoutGlobalScopes()
                ->where('order_id', $order->id)
                ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery', 'waiting_assignment', 'pending'])
                ->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to update DeliveryTask status for order #{$order->id}: {$e->getMessage()}");
        }

        // Post to folio/charges if room service
        try {
            $this->restaurantChargeService->createFromOrder($order);
        } catch (\Throwable $e) {
            Log::warning("Restaurant charge creation failed for order #{$order->id}: {$e->getMessage()}");
        }

        // Notify chefs
        $this->notifyChefs(
            'order_completed',
            'Order Completed',
            "Order #{$order->order_number} has been served.",
            $order
        );

        return $this->loadOrderRelations($order->fresh());
    }

    /**
     * Handle transition to CANCELLED.
     */
    protected function handleCancelled(Order $order, ?string $reason = null): Order
    {
        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        // Cancel pending/active delivery tasks
        try {
            DeliveryTask::withoutGlobalScopes()
                ->where('order_id', $order->id)
                ->where('status', '!=', 'delivered')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason ?? 'Order cancelled',
                ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to cancel DeliveryTask for order #{$order->id}: {$e->getMessage()}");
        }

        return $this->loadOrderRelations($order->fresh());
    }

    /**
     * Locate or assign waiter, and dispatch pickup notifications.
     */
    protected function notifyAssignedWaiterOnReady(Order $order): void
    {
        try {
            $deliveryTask = DeliveryTask::withoutGlobalScopes()
                ->where('order_id', $order->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            // If task exists and has waiter assigned, notify them directly
            if ($deliveryTask && $deliveryTask->waiter_id) {
                $waiter = Waiter::find($deliveryTask->waiter_id);
                if ($waiter) {
                    $waiterUser = $waiter->user ?? User::find($waiter->user_id);
                    if ($waiterUser) {
                        $this->waiterNotificationService->notifyOrderReady($waiterUser, $deliveryTask);
                    }

                    // Create structured WaiterNotification record
                    WaiterNotification::create([
                        'hotel_id' => $order->hotel_id,
                        'waiter_id' => $waiter->id,
                        'delivery_task_id' => $deliveryTask->id,
                        'order_id' => $order->id,
                        'type' => 'order_ready',
                        'title' => "Order Ready: #{$order->order_number}",
                        'message' => "Order #{$order->order_number} is ready for pickup/delivery.",
                        'data' => [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'delivery_task_id' => $deliveryTask->id,
                            'room_number' => $order->room?->room_number,
                            'table_number' => $order->table?->table_number,
                        ],
                        'read' => false,
                        'is_read' => false,
                    ]);

                    Log::info("[ORDER_STATUS] Notified assigned waiter {$waiter->id} for ready order #{$order->order_number}");
                    return;
                }
            }

            // If no delivery task yet or no waiter assigned, trigger automatic assignment engine
            $assignResult = $this->waiterAssignmentService->assignWaiterToReadyOrder($order);
            Log::info("[ORDER_STATUS] Executed automatic waiter assignment on ready order #{$order->order_number}", $assignResult);

        } catch (\Throwable $e) {
            Log::error("[ORDER_STATUS] Failed notifying waiter on order ready: {$e->getMessage()}", [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Notify chefs belonging to the order's hotel.
     */
    protected function notifyChefs(string $type, string $title, string $message, ?Order $order = null): void
    {
        try {
            $hotelId = $order?->hotel_id ?? app(TenantContext::class)->getHotelId();

            $chefQuery = User::where(function ($q) {
                $q->where('role', 'chef')
                  ->orWhereHas('roles', fn($rq) => $rq->where('slug', 'chef'));
            });

            if ($hotelId) {
                $chefQuery->whereHas('hotelMemberships', fn($m) => $m->where('hotel_id', $hotelId)->where('is_active', true));
            }

            $chefs = $chefQuery->get();

            foreach ($chefs as $chef) {
                Notification::create([
                    'user_id' => $chef->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'read' => false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create chef notifications: ' . $e->getMessage());
        }
    }

    /**
     * Eager load standard relations for the order.
     */
    public function loadOrderRelations(Order $order): Order
    {
        return $order->load([
            'guest' => fn($gq) => $gq->withoutGlobalScopes(),
            'room' => fn($rq) => $rq->withoutGlobalScopes(),
            'reservation' => fn($rvq) => $rvq->withoutGlobalScopes(),
            'orderItems',
            'orderItems.menuItem' => fn($mq) => $mq->withoutGlobalScopes(),
            'table' => fn($tq) => $tq->withoutGlobalScopes(),
        ]);
    }
}
