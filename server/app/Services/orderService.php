<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\MenuItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
class OrderService{
    public function index(int $perPage = 15): LengthAwarePaginator
       {
          return Order::query()
            ->with([
                'reservation',
                'guest',
                'room',
                'orderItems',
                'orderItems.menuItem',
            ])
            ->latest('order_time')
            ->paginate($perPage);
    }

    /**
     * Display a single order.
     */
    public function show(string $id): Order
    {
        return Order::query()
            ->with([
                'reservation',
                'guest',
                'room',
                'orderItems',
                'orderItems.menuItem',
            ])
            ->findOrFail($id);
    }
    private function validateReservation(string $reservationId): Reservation
    {
        $reservation = Reservation::query()
            ->with([
                'guest',
                'room',
            ])
            ->find($reservationId);

        if (! $reservation) {
            throw new ModelNotFoundException(
                'Reservation not found.'
            );
        }

        return $reservation;
    }
    private function validateGuest(
        Reservation $reservation,
        string $guestId
    ): void {

        if ($reservation->guest_id !== $guestId) {
            throw new Exception(
                'The selected guest does not belong to this reservation.'
            );
        }
    }
    private function validateRoom(
        Reservation $reservation,
        string $roomId
    ): void {

        if ($reservation->room_id !== $roomId) {
            throw new Exception(
                'The selected room does not belong to this reservation.'
            );
        }
    }
    private function getMenuItem(string $menuItemId): MenuItem
    {
        $menuItem = MenuItem::query()
            ->find($menuItemId);

        if (! $menuItem) {
            throw new ModelNotFoundException(
                'Menu item not found.'
            );
        }

        return $menuItem;
    }
    private function generateOrderNumber(): string
    {
        do {

            $number = sprintf(
                'ORD-%s-%s',
                now()->format('YmdHis'),
                strtoupper(Str::random(4))
            );

        } while (
            Order::where('order_number', $number)->exists()
        );

        return $number;
    }
    /**
 * Create a new restaurant order.
 */
public function create(array $data): Order
{
    DB::beginTransaction();

    try {
        $reservation = $this->validateReservation(
            $data['reservation_id']
        );

        $this->validateGuest(
            $reservation,
            $data['guest_id']
        );

        $this->validateRoom(
            $reservation,
            $data['room_id']
        );

        $subtotal = 0;
        $tax = 0;
        $discount = 0;
        $total = 0;

        $order = Order::create([

            'order_number' => $this->generateOrderNumber(),

            'reservation_id' => $reservation->id,

            'guest_id' => $reservation->guest_id,

            'room_id' => $reservation->room_id,

            'order_time' => now(),

            'status' => Order::STATUS_PENDING,

            'payment_type' => 'room_charge',

            'subtotal' => $subtotal,

            'tax' => $tax,

            'discount' => $discount,

            'total' => $total,

            'notes' => $data['notes'] ?? null,
            

        ]);
        foreach ($data['items'] as $item) {

            /*
            |--------------------------------------------------------------------------
            | Get Menu Item
            |--------------------------------------------------------------------------
            */

            $menuItem = $this->getMenuItem(
                $item['menu_item_id']
            );
            $lineTotal = $menuItem->price * $item['quantity'];

        

            $order->orderItems()->create([

                'menu_item_id' => $menuItem->id,

                'quantity' => $item['quantity'],

                'item_price_at_order' => $menuItem->price,

                'line_total' => $lineTotal,

                'notes' => $item['notes'] ?? null,

            ]);
            $subtotal += $lineTotal;
        }
        $tax = 0;

        $discount = 0;

        $total = ($subtotal + $tax) - $discount;

        $order->update([

            'subtotal' => $subtotal,

            'tax' => $tax,

            'discount' => $discount,

            'total' => $total,

        ]);
        DB::commit();
        return $order->fresh()->load([

            'reservation',

            'guest',

            'room',

            'orderItems',

            'orderItems.menuItem',

        ]);

    } catch (\Throwable $exception) {

        DB::rollBack();

        throw $exception;
    }
}
/**
 * Update an existing restaurant order.
 */
public function update(string $id, array $data): Order
{
    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Find Order
        |--------------------------------------------------------------------------
        */

        $order = Order::query()
            ->with('orderItems')
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Business Rule
        |--------------------------------------------------------------------------
        | Only pending orders can be modified.
        |--------------------------------------------------------------------------
        */

        if ($order->status !== Order::STATUS_PENDING) {
            throw new Exception(
                'Only pending orders can be updated.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Reservation
        |--------------------------------------------------------------------------
        */

        $reservation = $this->validateReservation(
            $data['reservation_id']
        );

        $this->validateGuest(
            $reservation,
            $data['guest_id']
        );

        $this->validateRoom(
            $reservation,
            $data['room_id']
        );

        /*
        |--------------------------------------------------------------------------
        | Update Basic Information
        |--------------------------------------------------------------------------
        */

        $order->update([

            'notes' => $data['notes'] ?? null,

        ]);
                /*
        |--------------------------------------------------------------------------
        | Synchronize Order Items
        |--------------------------------------------------------------------------
        */

        $existingItems = $order->orderItems()
            ->get()
            ->keyBy('id');

        $submittedItemIds = [];

        $subtotal = 0;

        foreach ($data['items'] as $itemData) {

            /*
            |--------------------------------------------------------------------------
            | Validate Menu Item
            |--------------------------------------------------------------------------
            */

            $menuItem = $this->getMenuItem(
                $itemData['menu_item_id']
            );

            /*
            |--------------------------------------------------------------------------
            | Calculate Line Total
            |--------------------------------------------------------------------------
            */

            $lineTotal = $menuItem->price * $itemData['quantity'];

            /*
            |--------------------------------------------------------------------------
            | Existing Item
            |--------------------------------------------------------------------------
            */

            if (
                !empty($itemData['id']) &&
                $existingItems->has($itemData['id'])
            ) {

                $orderItem = $existingItems->get(
                    $itemData['id']
                );

                $orderItem->update([

                    'menu_item_id' => $menuItem->id,

                    'quantity' => $itemData['quantity'],

                    'item_price_at_order' => $menuItem->price,

                    'line_total' => $lineTotal,

                    'notes' => $itemData['notes'] ?? null,

                ]);

                $submittedItemIds[] = $orderItem->id;
            }

            /*
            |--------------------------------------------------------------------------
            | New Item
            |--------------------------------------------------------------------------
            */

            else {

                $newItem = $order->orderItems()->create([

                    'menu_item_id' => $menuItem->id,

                    'quantity' => $itemData['quantity'],

                    'item_price_at_order' => $menuItem->price,

                    'line_total' => $lineTotal,

                    'notes' => $itemData['notes'] ?? null,

                ]);

                $submittedItemIds[] = $newItem->id;
            }

            /*
            |--------------------------------------------------------------------------
            | Running Subtotal
            |--------------------------------------------------------------------------
            */

            $subtotal += $lineTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Removed Items
        |--------------------------------------------------------------------------
        */

        $order->orderItems()
            ->whereNotIn('id', $submittedItemIds)
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Continue in Part 2.3
        |--------------------------------------------------------------------------
        */
                /*
        |--------------------------------------------------------------------------
        | Recalculate Financial Values
        |--------------------------------------------------------------------------
        */

        $tax = $this->calculateTax($subtotal);

        $discount = $this->calculateDiscount(
            $subtotal,
            $reservation
        );

        $total = $this->calculateTotal(
            $subtotal,
            $tax,
            $discount
        );

        /*
        |--------------------------------------------------------------------------
        | Update Order Totals
        |--------------------------------------------------------------------------
        */

        $order->update([

            'subtotal' => $subtotal,

            'tax' => $tax,

            'discount' => $discount,

            'total' => $total,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Commit Transaction
        |--------------------------------------------------------------------------
        */

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Return Updated Order
        |--------------------------------------------------------------------------
        */

        return $order->fresh()->load([

            'reservation',

            'guest',

            'room',

            'orderItems',

            'orderItems.menuItem',

        ]);

    } catch (\Throwable $exception) {

        DB::rollBack();

        throw $exception;
    }
}
/**
 * Calculate tax.
 */
private function calculateTax(float $subtotal): float
{
    // Example:
    // return round($subtotal * 0.15, 2);

    return 0;
}

/**
 * Calculate discount based on reservation and loyalty.
 */
private function calculateDiscount(
    float $subtotal,
    Reservation $reservation
): float {
    // Example discount logic:
    // - VIP guests: 10% discount
    // - Early booking: 5% discount
    // Add your business logic here

    return 0;
}

/**
 * Calculate total with all adjustments.
 */
private function calculateTotal(
    float $subtotal,
    float $tax,
    float $discount
): float {
    return round(($subtotal + $tax) - $discount, 2);
}

/**
 * Change order status.
 */
public function changeStatus(string $id, string $status): Order
{
    $order = Order::query()->findOrFail($id);

    // Only pending orders can transition to other states
    if (
        $order->status === Order::STATUS_PENDING &&
        in_array(
            $status,
            [
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
                Order::STATUS_SERVED,
                Order::STATUS_CANCELLED,
            ]
        )
    ) {
        // Valid transition
    } elseif ($order->status === Order::STATUS_PREPARING &&
        in_array($status, [Order::STATUS_READY, Order::STATUS_CANCELLED])
    ) {
        // Valid transition
    } elseif ($order->status === Order::STATUS_READY &&
        in_array($status, [Order::STATUS_SERVED, Order::STATUS_CANCELLED])
    ) {
        // Valid transition
    } elseif ($status === $order->status) {
        // No change needed
        return $order;
    } else {
        throw new Exception(
            "Cannot transition order status from {$order->status} to {$status}"
        );
    }

    // Update status and timestamps
    $updateData = ['status' => $status];

    if ($status === Order::STATUS_SERVED) {
        $updateData['served_at'] = now();
    } elseif ($status === Order::STATUS_CANCELLED) {
        $updateData['cancelled_at'] = now();
    }

    $order->update($updateData);

    return $order->fresh()->load([
        'reservation',
        'guest',
        'room',
        'orderItems',
        'orderItems.menuItem',
    ]);
}

/**
 * Cancel an order.
 */
public function cancel(string $id): void
{
    $order = Order::query()->findOrFail($id);

    if (
        !in_array(
            $order->status,
            [
                Order::STATUS_PENDING,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
            ]
        )
    ) {
        throw new Exception(
            "Cannot cancel an order with status: {$order->status}"
        );
    }

    $order->update([
        'status' => Order::STATUS_CANCELLED,
        'cancelled_at' => now(),
    ]);
}
}