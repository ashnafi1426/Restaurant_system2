<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RestaurantTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Guest;
use App\Models\Reservation;
use App\Services\QRResolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UnifiedOrderController extends Controller
{
    public function __construct()
    {
    }
    
    public function store(Request $request)
    {
        return $this->createOrder($request);
    }
    
    public function createOrder(Request $request)
    {
        try {
            Log::info('[UNIFIED ORDER] Creating order', [
                'qr_token' => $request->qr_token,
                'items_count' => count($request->get('items', [])),
            ]);

            $validated = $request->validate([
                'qr_token' => 'required|string|min:8|max:30',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
                'special_requests' => 'nullable|string|max:500',
                'payment_type' => 'nullable|in:room_charge,cash,card',
            ]);

            $qrToken = $validated['qr_token'];

            $resolution = QRResolutionService::resolveQRToken($qrToken);

            if (!$resolution['success']) {
                Log::warning('[UNIFIED ORDER] QR token resolution failed', [
                    'qr_token' => $qrToken,
                    'message' => $resolution['message'],
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Invalid QR code',
                    'message' => $resolution['message'],
                ], 404);
            }

            $context = $resolution['context'];
            $contextData = $resolution['data'];

            Log::info('[UNIFIED ORDER] QR token resolved', [
                'qr_token' => $qrToken,
                'context' => $context,
                'context_data' => $contextData,
            ]);

            if ($context === 'room') {
                return $this->createRoomServiceOrder($validated, $contextData);
            } elseif ($context === 'table') {
                return $this->createWalkInOrder($validated, $contextData);
            }

            return response()->json([
                'success' => false,
                'error' => 'Invalid context',
                'message' => 'Unknown order context',
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[UNIFIED ORDER] Validation error', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[UNIFIED ORDER] Error creating order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to create order',
            ], 500);
        }
    }

    protected function createRoomServiceOrder(array $validated, array $roomData)
    {
        return DB::transaction(function () use ($validated, $roomData) {
            $room = Room::findOrFail($roomData['room_id']);

            $reservation = DB::table('reservations')
                ->where('room_id', $room->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$reservation) {
                $guest = Guest::create([
                    'id' => Str::uuid(),
                    'first_name' => 'QR Guest',
                    'last_name' => $room->room_number,
                    'email' => 'qr-' . $room->room_number . '@hotel.local',
                    'phone' => '0000000000',
                ]);

                $reservationId = Str::uuid();
                DB::table('reservations')->insert([
                    'id' => $reservationId,
                    'booking_reference' => Reservation::generateBookingReference(),
                    'room_id' => $room->id,
                    'guest_id' => $guest->id,
                    'check_in_date' => now()->format('Y-m-d'),
                    'check_out_date' => now()->addDays(1)->format('Y-m-d'),
                    'status' => 'confirmed',
                    'number_of_guests' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $reservation = DB::table('reservations')->where('id', $reservationId)->first();
            }

            list($total, $orderItems) = $this->calculateOrderTotal($validated['items'], $room->hotel_id);

            $order = Order::create([
                'hotel_id' => $room->hotel_id,
                'order_number' => Order::generateOrderNumber(),
                'room_id' => $room->id,
                'guest_id' => $reservation->guest_id,
                'reservation_id' => $reservation->id,
                'table_id' => null,
                'order_type' => Order::TYPE_ROOM_SERVICE,
                'order_time' => now(),
                'total' => $total,
                'subtotal' => $total,
                'status' => Order::STATUS_PENDING,
                'payment_type' => $validated['payment_type'] ?? 'room_charge',
                'notes' => $validated['special_requests'] ?? null,
            ]);

            $this->createOrderItems($order->id, $orderItems);

            Log::info('[UNIFIED ORDER] Room service order created', [
                'order_id' => $order->id,
                'order_type' => 'room_service',
                'room_number' => $room->room_number,
                'total' => $total,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Room service order placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'room_number' => $room->room_number,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                ],
            ], 201);
        });
    }

    protected function createWalkInOrder(array $validated, array $tableData)
    {
        return DB::transaction(function () use ($validated, $tableData) {
            $table = RestaurantTable::findOrFail($tableData['table_id']);

            list($total, $orderItems) = $this->calculateOrderTotal($validated['items'], $table->hotel_id);

            $order = Order::create([
                'hotel_id' => $table->hotel_id,
                'order_number' => Order::generateOrderNumber(),
                'room_id' => null,
                'guest_id' => null,
                'reservation_id' => null,
                'table_id' => $table->id,
                'order_type' => Order::TYPE_WALK_IN,
                'order_time' => now(),
                'total' => $total,
                'subtotal' => $total,
                'status' => Order::STATUS_PENDING,
                'payment_type' => $validated['payment_type'] ?? 'cash',
                'notes' => $validated['special_requests'] ?? null,
            ]);

            $this->createOrderItems($order->id, $orderItems);

            $table->update(['status' => RestaurantTable::STATUS_OCCUPIED]);

            Log::info('[UNIFIED ORDER] Walk-in order created', [
                'order_id' => $order->id,
                'order_type' => 'walk_in',
                'table_number' => $table->table_number,
                'total' => $total,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Walk-in order placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'table_number' => $table->table_number,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                ],
            ], 201);
        });
    }

    protected function calculateOrderTotal(array $items, ?string $targetHotelId = null): array
    {
        $total = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);

            if ($targetHotelId && $menuItem->hotel_id && $menuItem->hotel_id !== $targetHotelId) {
                throw new \InvalidArgumentException("Menu item '{$menuItem->name}' does not belong to this hotel.");
            }

            $lineTotal = $menuItem->price * $item['quantity'];
            $total += $lineTotal;

            $orderItems[] = [
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'item_price_at_order' => $menuItem->price,
                'line_total' => $lineTotal,
            ];
        }

        return [$total, $orderItems];
    }

    protected function createOrderItems(string $orderId, array $orderItems): void
    {
        foreach ($orderItems as $item) {
            OrderItem::create([
                'order_id' => $orderId,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'item_price_at_order' => $item['item_price_at_order'],
                'line_total' => $item['line_total'],
            ]);
        }
    }
}
