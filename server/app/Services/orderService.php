<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\MenuItem;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
class OrderService{
    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()
            ->with([
                'reservation',
                'guest',
                'room',
                'table',
                'orderItems',
                'orderItems.menuItem',
            ]);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('guest', function ($gq) use ($search) {
                      $gq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"]);
                  })
                  ->orWhereHas('room', function ($rq) use ($search) {
                      $rq->where('room_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('table', function ($tq) use ($search) {
                      $tq->where('table_number', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', strtolower($filters['status']));
        }

        if (!empty($filters['payment_type'])) {
            $paymentType = strtolower($filters['payment_type']);
            if ($paymentType === 'card') {
                $query->whereIn('payment_type', ['card', 'chapa', 'online']);
            } else {
                $query->where('payment_type', $paymentType);
            }
        }

        if (!empty($filters['order_type'])) {
            $orderType = strtolower($filters['order_type']);
            if ($orderType === 'room_service') {
                $query->where(function ($q) {
                    $q->whereNotNull('room_id')
                      ->orWhere('order_type', 'room_service');
                });
            } elseif ($orderType === 'walk_in') {
                $query->where(function ($q) {
                    $q->whereNotNull('table_id')
                      ->orWhere('order_type', 'walk_in');
                });
            }
        }

        if (!empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'];
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('order_time', '>=', $dateFrom)
                  ->orWhere(function ($sq) use ($dateFrom) {
                      $sq->whereNull('order_time')
                         ->whereDate('created_at', '>=', $dateFrom);
                  });
            });
        }

        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'];
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('order_time', '<=', $dateTo)
                  ->orWhere(function ($sq) use ($dateTo) {
                      $sq->whereNull('order_time')
                         ->whereDate('created_at', '<=', $dateTo);
                  });
            });
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function getStatistics(): array
    {
        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', Order::STATUS_PENDING)->count(),
            'preparing_orders' => Order::where('status', Order::STATUS_PREPARING)->count(),
            'ready_orders' => Order::where('status', Order::STATUS_READY)->count(),
            'served_orders' => Order::where('status', Order::STATUS_SERVED)->count(),
            'cancelled_orders' => Order::where('status', Order::STATUS_CANCELLED)->count(),
            'total_revenue' => (float) Order::where('status', '!=', Order::STATUS_CANCELLED)->sum('total'),
        ];
    }
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
    private function assignChefToOrder(): ?string
    {
        try {
            $chefs = User::where('role', 'chef')->pluck('id')->toArray();
            
            if (empty($chefs)) {
                return null;
            }

            if (count($chefs) === 1) {
                return $chefs[0];
            }
            $chefWorkload = [];
            foreach ($chefs as $chefId) {
                $count = Order::where('chef_id', $chefId)
                    ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING])
                    ->count();
                $chefWorkload[$chefId] = $count;
            }
            $selectedChef = array_key_first($chefWorkload);
            foreach ($chefWorkload as $chefId => $count) {
                if ($count < $chefWorkload[$selectedChef]) {
                    $selectedChef = $chefId;
                }
            }
            return $selectedChef;
        } catch (\Exception $e) {
            \Log::error('Failed to assign chef: ' . $e->getMessage());
            // Fallback: return first chef
            $firstChef = User::where('role', 'chef')->first();
            return $firstChef?->id ?? null;
        }
    }
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

            'chef_id' => $this->assignChefToOrder(),

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
        
        // Notify chefs of the new order
        $this->notifyChefs($order);
        
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
private function notifyChefs(Order $order): void
{
    try {
        $chefs = User::where('role', 'chef')->get();
        
        foreach ($chefs as $chef) {
            Notification::create([
                'user_id' => $chef->id,
                'type' => 'order_created',
                'title' => 'New Order',
                'message' => 'Order #' . $order->order_number . ' has been created for processing',
                'read' => false,
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Failed to notify chefs of new order: ' . $e->getMessage());
    }
}
public function update(string $id, array $data): Order
{
    DB::beginTransaction();

    try {
        $order = Order::query()
            ->with('orderItems')
            ->findOrFail($id);

        if ($order->status !== Order::STATUS_PENDING) {
            throw new Exception(
                'Only pending orders can be updated.'
            );
        }

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

        $order->update([

            'notes' => $data['notes'] ?? null,

        ]);
        $existingItems = $order->orderItems()
            ->get()
            ->keyBy('id');

        $submittedItemIds = [];

        $subtotal = 0;

        foreach ($data['items'] as $itemData) {
            $menuItem = $this->getMenuItem(
                $itemData['menu_item_id']
            );

            $lineTotal = $menuItem->price * $itemData['quantity'];
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
            $subtotal += $lineTotal;
        }

        $order->orderItems()
            ->whereNotIn('id', $submittedItemIds)
            ->delete();
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
private function calculateTax(float $subtotal): float
{
    return 0;
}
private function calculateDiscount(
    float $subtotal,
    Reservation $reservation
): float {
    return 0;
}

private function calculateTotal(
    float $subtotal,
    float $tax,
    float $discount
): float {
    return round(($subtotal + $tax) - $discount, 2);
}

public function changeStatus(string $id, string $status): Order
{
    $order = Order::query()->findOrFail($id);

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
    } elseif ($order->status === Order::STATUS_PREPARING &&
        in_array($status, [Order::STATUS_READY, Order::STATUS_CANCELLED])
    ) {
    } elseif ($order->status === Order::STATUS_READY &&
        in_array($status, [Order::STATUS_SERVED, Order::STATUS_CANCELLED])
    ) {
    } elseif ($status === $order->status) {
        return $order;
    } else {
        throw new Exception(
            "Cannot transition order status from {$order->status} to {$status}"
        );
    }

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