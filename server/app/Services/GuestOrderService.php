<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\User;
use App\Services\QRResolutionService;
use App\Services\TenantContext;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuestOrderService
{
    /**
     * Process and place an order originating from a Guest QR scan (Room or Table).
     */
    public function placeOrder(array $data): array
    {
        $qrToken = $data['qr_token'];
        $resolution = QRResolutionService::resolveQRToken($qrToken);

        if (!$resolution['success']) {
            return [
                'status_code' => 404,
                'response' => [
                    'success' => false,
                    'error' => 'Invalid QR code',
                    'message' => $resolution['message'] ?? 'QR code token not found or expired.',
                ],
            ];
        }

        $context = $resolution['context'];
        $contextData = $resolution['data'];

        if ($context === 'room') {
            return $this->placeRoomOrder($data, $contextData);
        }

        if ($context === 'table') {
            return $this->placeTableOrder($data, $contextData);
        }

        return [
            'status_code' => 400,
            'response' => [
                'success' => false,
                'error' => 'Invalid context',
                'message' => 'Unrecognized ordering context.',
            ],
        ];
    }

    /**
     * Place a room-service order, enforcing checked-in reservation eligibility.
     */
    protected function placeRoomOrder(array $data, array $roomData): array
    {
        $room = Room::withoutGlobalScopes()->findOrFail($roomData['room_id']);
        $reservation = $room->getCurrentReservation();

        if (!$reservation || $reservation->status !== 'checked_in') {
            $statusText = $reservation ? $reservation->status : 'none';
            $errorMessage = match ($statusText) {
                'confirmed' => 'Your reservation is confirmed, but room-service ordering is only available after check-in at the front desk.',
                'checked_out' => 'This room has been checked out. Room-service ordering is no longer available.',
                'cancelled' => 'This reservation was cancelled. Room-service ordering is unavailable.',
                default => 'Room service is only available for checked-in guests. Please contact the front desk.',
            };

            return [
                'status_code' => 403,
                'response' => [
                    'success' => false,
                    'error' => 'Ordering not allowed',
                    'message' => $errorMessage,
                    'reservation_status' => $statusText,
                    'can_order' => false,
                ],
            ];
        }

        return DB::transaction(function () use ($data, $room, $reservation) {
            $hotelId = $room->hotel_id
                ?? app(TenantContext::class)->getHotelId()
                ?? \App\Models\Hotel::value('id');

            list($total, $orderItems) = $this->calculateOrderTotal($data['items'], $hotelId);

            $order = Order::create([
                'hotel_id' => $hotelId,
                'order_number' => Order::generateOrderNumber($hotelId),
                'room_id' => $room->id,
                'table_id' => null,
                'guest_id' => $reservation->guest_id,
                'reservation_id' => $reservation->id,
                'order_type' => Order::TYPE_ROOM_SERVICE,
                'order_time' => now(),
                'total' => $total,
                'subtotal' => $total,
                'status' => Order::STATUS_PENDING,
                'source' => 'guest_qr',
                'payment_type' => $data['payment_type'] ?? 'room_charge',
                'notes' => $data['special_requests'] ?? null,
            ]);

            $this->createOrderItems($order->id, $orderItems);
            $this->notifyChefs($order, "Room order #{$order->order_number} (Room {$room->room_number}) has been placed.");

            // Automatic Waiter Assignment: Room -> Floor -> Assigned Waiters -> Workload -> Assigned
            try {
                app(AutomaticWaiterAssignmentService::class)->assignWaiterToReadyOrder($order);
            } catch (\Throwable $e) {
                Log::warning("Automatic waiter assignment failed for room order #{$order->id}: {$e->getMessage()}");
            }

            return [
                'status_code' => 201,
                'response' => [
                    'success' => true,
                    'message' => 'Room service order placed successfully',
                    'data' => [
                        'id' => $order->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_type' => $order->order_type,
                        'room_number' => $room->room_number,
                        'total' => (float) $order->total,
                        'status' => $order->status,
                        'items' => $orderItems,
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                ],
            ];
        });
    }

    /**
     * Place a walk-in table order (does NOT require hotel reservation check-in).
     */
    protected function placeTableOrder(array $data, array $tableData): array
    {
        return DB::transaction(function () use ($data, $tableData) {
            $table = RestaurantTable::withoutGlobalScopes()->findOrFail($tableData['table_id']);
            $hotelId = $table->hotel_id
                ?? app(TenantContext::class)->getHotelId()
                ?? \App\Models\Hotel::value('id');

            if ($table && empty($table->hotel_id) && $hotelId) {
                $table->update(['hotel_id' => $hotelId]);
            }

            list($total, $orderItems) = $this->calculateOrderTotal($data['items'], $hotelId);

            $order = Order::create([
                'hotel_id' => $hotelId,
                'order_number' => Order::generateOrderNumber($hotelId),
                'room_id' => null,
                'table_id' => $table->id,
                'guest_id' => null,
                'reservation_id' => null,
                'order_type' => Order::TYPE_WALK_IN,
                'order_time' => now(),
                'total' => $total,
                'subtotal' => $total,
                'status' => Order::STATUS_PENDING,
                'source' => 'guest_qr',
                'payment_type' => $data['payment_type'] ?? 'cash',
                'notes' => $data['special_requests'] ?? null,
            ]);

            $this->createOrderItems($order->id, $orderItems);
            $table->update(['status' => RestaurantTable::STATUS_OCCUPIED]);

            $this->notifyChefs($order, "Table order #{$order->order_number} (Table {$table->table_number}) has been placed.");

            // Automatic Waiter Assignment: Table -> Section -> Assigned Waiters -> Workload -> Assigned
            try {
                app(AutomaticWaiterAssignmentService::class)->assignWaiterToReadyOrder($order);
            } catch (\Throwable $e) {
                Log::warning("Automatic waiter assignment failed for table order #{$order->id}: {$e->getMessage()}");
            }

            return [
                'status_code' => 201,
                'response' => [
                    'success' => true,
                    'message' => 'Table order placed successfully',
                    'data' => [
                        'id' => $order->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_type' => $order->order_type,
                        'table_number' => $table->table_number,
                        'total' => (float) $order->total,
                        'status' => $order->status,
                        'items' => $orderItems,
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                ],
            ];
        });
    }

    /**
     * Compute line totals and validate menu items.
     */
    public function calculateOrderTotal(array $items, ?string $hotelId): array
    {
        $total = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $menuItem = MenuItem::withoutGlobalScopes()->findOrFail($item['menu_item_id']);

            if ($hotelId && $menuItem->hotel_id && $menuItem->hotel_id !== $hotelId) {
                throw new \InvalidArgumentException("Menu item '{$menuItem->name}' does not belong to hotel {$hotelId}");
            }

            $lineTotal = round((float) $menuItem->price * (int) $item['quantity'], 2);
            $total += $lineTotal;

            $orderItems[] = [
                'menu_item_id' => $menuItem->id,
                'quantity' => (int) $item['quantity'],
                'item_price_at_order' => (float) $menuItem->price,
                'line_total' => $lineTotal,
            ];
        }

        return [round($total, 2), $orderItems];
    }

    /**
     * Persist order items.
     */
    protected function createOrderItems(string $orderId, array $items): void
    {
        foreach ($items as $item) {
            $subtotal = $item['line_total'] ?? 0;
            OrderItem::create([
                'order_id' => $orderId,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'item_price_at_order' => $item['item_price_at_order'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'line_total' => $subtotal,
            ]);
        }
    }

    /**
     * Send order notifications to on-duty chefs for the order's hotel.
     */
    protected function notifyChefs(Order $order, string $message): void
    {
        try {
            $hotelId = $order->hotel_id;
            $chefs = User::where('role', 'chef')
                ->when($hotelId, function ($q) use ($hotelId) {
                    $q->where(function ($sub) use ($hotelId) {
                        $sub->where('hotel_id', $hotelId)
                            ->orWhereHas('hotelMemberships', fn($hq) => $hq->where('hotel_id', $hotelId));
                    });
                })
                ->get();

            foreach ($chefs as $chef) {
                Notification::create([
                    'user_id' => $chef->id,
                    'type' => 'order_created',
                    'title' => 'New Order Received',
                    'message' => $message,
                    'read' => false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send chef notification: ' . $e->getMessage());
        }
    }
}