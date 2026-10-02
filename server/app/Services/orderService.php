<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\MenuItem;
use App\Models\User;
use App\Models\Notification;
use App\Services\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class OrderService{
    protected OrderStatusService $orderStatusService;

    public function __construct(?OrderStatusService $orderStatusService = null)
    {
        $this->orderStatusService = $orderStatusService ?? app(OrderStatusService::class);
    }

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
        $hotelId = TenantContext::id() ?? app(TenantContext::class)->getHotelId();
        $query = Order::query();
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $stats = (clone $query)->selectRaw("
            status,
            COUNT(*) as count,
            SUM(total) as revenue
        ")->groupBy('status')->get()->keyBy('status');

        $pending = $stats->get(Order::STATUS_PENDING);
        $preparing = $stats->get(Order::STATUS_PREPARING);
        $ready = $stats->get(Order::STATUS_READY);
        $served = $stats->get(Order::STATUS_SERVED);
        $cancelled = $stats->get(Order::STATUS_CANCELLED);

        $totalRevenue = $stats->filter(fn($v, $k) => $k !== Order::STATUS_CANCELLED)->sum('revenue');

        return [
            'total_orders' => (int) $stats->sum('count'),
            'pending_orders' => (int) ($pending->count ?? 0),
            'preparing_orders' => (int) ($preparing->count ?? 0),
            'ready_orders' => (int) ($ready->count ?? 0),
            'served_orders' => (int) ($served->count ?? 0),
            'cancelled_orders' => (int) ($cancelled->count ?? 0),
            'total_revenue' => (float) $totalRevenue,
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
            ->with('taxRate')
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

    private function assignChefToOrder(?string $hotelId = null): ?string
    {
        try {
            $chefQuery = User::where('role', 'chef');
            if ($hotelId) {
                $chefQuery->where(function ($q) use ($hotelId) {
                    $q->where('hotel_id', $hotelId)
                      ->orWhereHas('hotelMemberships', fn($m) => $m->where('hotel_id', $hotelId));
                });
            }

            $chefs = $chefQuery->pluck('id')->toArray();
            if (empty($chefs)) {
                return null;
            }

            if (count($chefs) === 1) {
                return $chefs[0];
            }

            $workloads = Order::whereIn('chef_id', $chefs)
                ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING])
                ->selectRaw('chef_id, count(*) as count')
                ->groupBy('chef_id')
                ->pluck('count', 'chef_id')
                ->toArray();

            $leastWorkload = PHP_INT_MAX;
            $selectedChef = $chefs[0];

            foreach ($chefs as $chefId) {
                $count = $workloads[$chefId] ?? 0;
                if ($count < $leastWorkload) {
                    $leastWorkload = $count;
                    $selectedChef = $chefId;
                }
            }

            return $selectedChef;
        } catch (\Throwable $e) {
            Log::error('Failed to assign chef: ' . $e->getMessage());
            return null;
        }
    }

    public function calculateItemTax(MenuItem $menuItem, int $quantity): array
    {
        $price = (float) $menuItem->price;
        $taxRate = $menuItem->taxRate;
        $rate = $taxRate ? (float) $taxRate->rate : 0.0;
        $taxIncluded = (bool) $menuItem->tax_included;

        if ($taxIncluded) {
            $lineTotal = round($price * $quantity, 2);
            $subtotal = $rate > 0 ? round($lineTotal / (1 + ($rate / 100)), 2) : $lineTotal;
            $taxAmount = round($lineTotal - $subtotal, 2);
        } else {
            $subtotal = round($price * $quantity, 2);
            $taxAmount = $rate > 0 ? round($subtotal * ($rate / 100), 2) : 0.0;
            $lineTotal = round($subtotal + $taxAmount, 2);
        }

        return [
            'price' => $price,
            'quantity' => $quantity,
            'tax_rate_id' => $taxRate?->id,
            'tax_rate' => $rate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'total' => $lineTotal,
        ];
    }

    public function create(array $data): Order
    {
        DB::beginTransaction();

        try {
            $orderType = $data['order_type'] ?? null;
            if (!$orderType) {
                if (!empty($data['room_id']) || !empty($data['reservation_id'])) {
                    $orderType = Order::TYPE_ROOM_SERVICE;
                } elseif (!empty($data['table_id'])) {
                    $orderType = Order::TYPE_DINE_IN;
                } else {
                    $orderType = Order::TYPE_WALK_IN;
                }
            }

            $hotelId = $data['hotel_id'] ?? TenantContext::id() ?? app(TenantContext::class)->getHotelId();

            $reservation = null;
            $guest = null;
            $room = null;
            $table = null;

            if ($orderType === Order::TYPE_ROOM_SERVICE) {
                // Room service requires valid room context and an active checked-in reservation
                if (!empty($data['reservation_id'])) {
                    $reservation = $this->validateReservation($data['reservation_id']);
                    if ($reservation->status !== 'checked_in') {
                        throw new Exception("Room service is only available for checked-in reservations. Current status: {$reservation->status}");
                    }
                    $room = $reservation->room;
                    $guest = $reservation->guest;
                    if (!empty($data['room_id']) && $room && $room->id !== $data['room_id']) {
                        throw new Exception("The selected room does not belong to this reservation.");
                    }
                    if (!empty($data['guest_id']) && $guest && $guest->id !== $data['guest_id']) {
                        throw new Exception("The selected guest does not belong to this reservation.");
                    }
                } elseif (!empty($data['room_id'])) {
                    $room = \App\Models\Room::findOrFail($data['room_id']);
                    $reservation = Reservation::where('room_id', $room->id)
                        ->where('status', 'checked_in')
                        ->latest()
                        ->first();
                    if (!$reservation) {
                        throw new Exception("Room service orders require an active checked-in reservation for Room {$room->room_number}.");
                    }
                    $guest = $reservation->guest;
                } else {
                    throw new Exception("Room service orders require a valid room.");
                }

                $hotelId = $hotelId ?? $reservation->hotel_id ?? $room?->hotel_id;
            } elseif ($orderType === Order::TYPE_DINE_IN) {
                // Dine-in orders require restaurant table context. Do not require hotel reservation or check-in.
                if (empty($data['table_id'])) {
                    throw new Exception("Dine-in orders require a restaurant table.");
                }
                $table = \App\Models\RestaurantTable::findOrFail($data['table_id']);
                $hotelId = $hotelId ?? $table->hotel_id;
                $table->update(['status' => \App\Models\RestaurantTable::STATUS_OCCUPIED]);
            } elseif ($orderType === Order::TYPE_WALK_IN) {
                // Walk-in orders do not require a hotel reservation or check-in. Table is optional.
                if (!empty($data['table_id'])) {
                    $table = \App\Models\RestaurantTable::findOrFail($data['table_id']);
                    $hotelId = $hotelId ?? $table->hotel_id;
                    $table->update(['status' => \App\Models\RestaurantTable::STATUS_OCCUPIED]);
                }
            }

            if ($hotelId) {
                app(\App\Services\TenantContext::class)->setHotelId($hotelId);
            }

            $discount = isset($data['discount']) ? max(0, (float) $data['discount']) : 0.0;
            $serviceChargeRate = isset($data['service_charge_rate']) ? max(0, (float) $data['service_charge_rate']) : 0.0;

            $order = Order::create([
                'hotel_id' => $hotelId,
                'order_number' => Order::generateOrderNumber($hotelId),
                'reservation_id' => $reservation?->id,
                'guest_id' => $guest?->id ?? ($data['guest_id'] ?? null),
                'room_id' => $room?->id,
                'table_id' => $table?->id,
                'order_type' => $orderType,
                'order_time' => now(),
                'status' => Order::STATUS_PENDING,
                'payment_type' => $data['payment_type'] ?? ($orderType === Order::TYPE_ROOM_SERVICE ? 'room_charge' : 'cash'),
                'subtotal' => 0,
                'taxable_amount' => 0,
                'tax' => 0,
                'service_charge_rate' => $serviceChargeRate,
                'service_charge_amount' => 0,
                'discount' => $discount,
                'total' => 0,
                'notes' => $data['notes'] ?? null,
                'chef_id' => $this->assignChefToOrder($hotelId),
            ]);

            $orderSubtotal = 0;
            $orderTax = 0;

            foreach ($data['items'] as $item) {
                $menuItem = $this->getMenuItem($item['menu_item_id']);
                if ($hotelId && $menuItem->hotel_id && $menuItem->hotel_id !== $hotelId) {
                    throw new Exception("Menu item '{$menuItem->name}' does not belong to this hotel.");
                }
                if (!$menuItem->is_available) {
                    throw new Exception("Menu item '{$menuItem->name}' is currently unavailable.");
                }

                $calc = $this->calculateItemTax($menuItem, (int) $item['quantity']);

                $order->orderItems()->create([
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'quantity' => $calc['quantity'],
                    'item_price_at_order' => $calc['price'],
                    'tax_rate_id' => $calc['tax_rate_id'],
                    'tax_rate' => $calc['tax_rate'],
                    'tax_amount' => $calc['tax_amount'],
                    'subtotal' => $calc['subtotal'],
                    'total' => $calc['total'],
                    'line_total' => $calc['total'],
                    'notes' => $item['notes'] ?? null,
                ]);

                $orderSubtotal += $calc['subtotal'];
                $orderTax += $calc['tax_amount'];
            }

            $serviceChargeAmount = isset($data['service_charge_amount']) 
                ? max(0, (float) $data['service_charge_amount']) 
                : ($serviceChargeRate > 0 ? round($orderSubtotal * ($serviceChargeRate / 100), 2) : 0.0);

            $total = round(max(0, ($orderSubtotal + $orderTax + $serviceChargeAmount) - $discount), 2);

            $order->update([
                'subtotal' => round($orderSubtotal, 2),
                'taxable_amount' => round($orderSubtotal, 2),
                'tax' => round($orderTax, 2),
                'service_charge_rate' => $serviceChargeRate,
                'service_charge_amount' => $serviceChargeAmount,
                'discount' => $discount,
                'total' => $total,
            ]);

            $this->notifyChefs($order);

            try {
                app(\App\Services\Waiter\AutomaticWaiterAssignmentService::class)->assignWaiterToReadyOrder($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Automatic waiter assignment failed for order #{$order->id}: {$e->getMessage()}");
            }

            DB::commit();

            return $order->fresh()->load([
                'reservation',
                'guest',
                'room',
                'table',
                'orderItems',
                'orderItems.menuItem',
                'orderItems.taxRate',
            ]);

        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
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
                throw new Exception('Only pending orders can be updated.');
            }

            if (!empty($data['notes'])) {
                $order->notes = $data['notes'];
            }
            if (!empty($data['payment_type'])) {
                $order->payment_type = $data['payment_type'];
            }
            if (isset($data['discount'])) {
                $order->discount = max(0, (float) $data['discount']);
            }
            if (isset($data['service_charge_rate'])) {
                $order->service_charge_rate = max(0, (float) $data['service_charge_rate']);
            }

            $order->save();

            if (!empty($data['items'])) {
                $existingItems = $order->orderItems()->get()->keyBy('id');
                $submittedItemIds = [];
                $orderSubtotal = 0;
                $orderTax = 0;

                foreach ($data['items'] as $itemData) {
                    $menuItem = $this->getMenuItem($itemData['menu_item_id']);
                    $calc = $this->calculateItemTax($menuItem, (int) $itemData['quantity']);

                    $itemPayload = [
                        'menu_item_id' => $menuItem->id,
                        'item_name' => $menuItem->name,
                        'quantity' => $calc['quantity'],
                        'item_price_at_order' => $calc['price'],
                        'tax_rate_id' => $calc['tax_rate_id'],
                        'tax_rate' => $calc['tax_rate'],
                        'tax_amount' => $calc['tax_amount'],
                        'subtotal' => $calc['subtotal'],
                        'total' => $calc['total'],
                        'line_total' => $calc['total'],
                        'notes' => $itemData['notes'] ?? null,
                    ];

                    if (!empty($itemData['id']) && $existingItems->has($itemData['id'])) {
                        $orderItem = $existingItems->get($itemData['id']);
                        $orderItem->update($itemPayload);
                        $submittedItemIds[] = $orderItem->id;
                    } else {
                        $newItem = $order->orderItems()->create($itemPayload);
                        $submittedItemIds[] = $newItem->id;
                    }

                    $orderSubtotal += $calc['subtotal'];
                    $orderTax += $calc['tax_amount'];
                }

                $order->orderItems()
                    ->whereNotIn('id', $submittedItemIds)
                    ->delete();

                $discount = (float) $order->discount;
                $serviceChargeRate = (float) $order->service_charge_rate;
                $serviceChargeAmount = isset($data['service_charge_amount'])
                    ? max(0, (float) $data['service_charge_amount'])
                    : ($serviceChargeRate > 0 ? round($orderSubtotal * ($serviceChargeRate / 100), 2) : 0.0);

                $total = round(max(0, ($orderSubtotal + $orderTax + $serviceChargeAmount) - $discount), 2);

                $order->update([
                    'subtotal' => round($orderSubtotal, 2),
                    'taxable_amount' => round($orderSubtotal, 2),
                    'tax' => round($orderTax, 2),
                    'service_charge_amount' => $serviceChargeAmount,
                    'total' => $total,
                ]);
            }

            DB::commit();

            return $order->fresh()->load([
                'reservation',
                'guest',
                'room',
                'table',
                'orderItems',
                'orderItems.menuItem',
                'orderItems.taxRate',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
    private function notifyChefs(Order $order): void
    {
        try {
            $hotelId = $order->hotel_id;
            $chefQuery = User::where('role', 'chef');
            if ($hotelId) {
                $chefQuery->where(function ($q) use ($hotelId) {
                    $q->where('hotel_id', $hotelId)
                      ->orWhereHas('hotelMemberships', fn($m) => $m->where('hotel_id', $hotelId));
                });
            }

            $chefs = $chefQuery->get();

            foreach ($chefs as $chef) {
                Notification::create([
                    'user_id' => $chef->id,
                    'type' => 'order_created',
                    'title' => 'New Order',
                    'message' => 'Order #' . $order->order_number . ' has been created for processing',
                    'read' => false,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify chefs of new order: ' . $e->getMessage());
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

        return $this->orderStatusService->transition($order, $status);
    }

    public function cancel(string $id, ?string $reason = null): void
    {
        $order = Order::query()->findOrFail($id);

        $this->orderStatusService->cancel($order, $reason);
    }
}