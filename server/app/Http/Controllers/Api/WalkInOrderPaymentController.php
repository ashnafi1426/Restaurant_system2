<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\ChapaService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class WalkInOrderPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected ChapaService $chapaService
    ) {}

    /**
     * Initialize Chapa online payment for a walk-in / table order.
     */
    public function initializePayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'table_id' => 'nullable|string',
                'qr_token' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
                'special_requests' => 'nullable|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
            ]);

            $table = null;
            if (!empty($validated['table_id'])) {
                $table = RestaurantTable::where('id', $validated['table_id'])
                    ->orWhere('table_number', $validated['table_id'])
                    ->first();
            }
            if (!$table && !empty($validated['qr_token'])) {
                $table = RestaurantTable::where('qr_token', $validated['qr_token'])
                    ->orWhere('table_number', $validated['qr_token'])
                    ->first();
            }
            if (!$table) {
                $table = RestaurantTable::where('is_active', true)->first();
            }

            if (!$table) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found for this order.',
                ], 404);
            }

            $orderCalculation = $this->calculateOrderTotal($validated['items']);
            if (!$orderCalculation['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $orderCalculation['message'],
                ], 400);
            }

            $orderItemsWithPrices = [];
            foreach ($orderCalculation['items'] as $item) {
                $orderItemsWithPrices[] = [
                    'menu_item_id' => $item['menu_item_id'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ];
            }

            $hotelId = $table->hotel_id
                ?? TenantContext::id()
                ?? Hotel::value('id');

            if ($table && empty($table->hotel_id) && $hotelId) {
                $table->update(['hotel_id' => $hotelId]);
            }

            $guestId = null;
            try {
                $guestQuery = Guest::withoutGlobalScopes()->where('email', $validated['email']);
                if ($hotelId) {
                    $guestQuery->where(function ($q) use ($hotelId) {
                        $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
                    });
                }
                $guest = $guestQuery->first();

                if (!$guest) {
                    $guest = Guest::create([
                        'id' => (string) Str::uuid(),
                        'hotel_id' => $hotelId,
                        'first_name' => $validated['first_name'] ?: 'Walk-in',
                        'last_name' => $validated['last_name'] ?: 'Guest',
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?: 'N/A',
                    ]);
                }
                $guestId = $guest->id;
            } catch (Throwable $guestErr) {
                Log::warning('Could not resolve or create guest for walk-in order: ' . $guestErr->getMessage());
            }

            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'tx_ref' => 'WALKIN-' . strtoupper(Str::random(12)),
                'amount' => $orderCalculation['total'],
                'currency' => 'ETB',
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => 'pending',
                'payment_method' => 'chapa',
                'metadata' => [
                    'type' => 'walk_in_order',
                    'hotel_id' => $hotelId,
                    'guest_id' => $guestId,
                    'table_id' => $table->id,
                    'table_number' => $table->table_number,
                    'qr_token' => $validated['qr_token'],
                    'items' => $orderItemsWithPrices,
                    'special_requests' => $validated['special_requests'] ?? null,
                    'calculation' => $orderCalculation,
                ],
            ]);

            $chapaResponse = $this->chapaService->initialize([
                'amount' => $payment->amount,
                'currency' => 'ETB',
                'email' => $payment->email,
                'first_name' => $payment->first_name,
                'last_name' => $payment->last_name,
                'phone' => $payment->phone,
                'tx_ref' => $payment->tx_ref,
                'callback_url' => config('chapa.callback_url'),
                'return_url' => config('chapa.order_return_url', config('app.frontend_url') . '/order/payment/success') . '?tx_ref=' . $payment->tx_ref,
                'title' => 'Table Order',
                'description' => sprintf(
                    'Table %s - %d items',
                    $table->table_number,
                    count($validated['items'])
                ),
            ]);

            if (!$chapaResponse['success']) {
                Log::error('Chapa Initialize Failed for Walk-In Order', [
                    'payment_id' => $payment->id,
                    'error' => $chapaResponse['message'] ?? 'Unknown error',
                    'response' => $chapaResponse,
                ]);

                $payment->update([
                    'status' => 'failed',
                    'error_message' => $chapaResponse['message'] ?? 'Payment initialization failed',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment with Chapa',
                    'error' => $chapaResponse['errors'] ?? ($chapaResponse['message'] ?? 'Payment gateway error'),
                ], 400);
            }

            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            $payment->update([
                'checkout_url' => $checkoutUrl,
                'status' => 'initialized',
            ]);

            Log::info('Walk-In Order Payment Initialized', [
                'payment_id' => $payment->id,
                'table_id' => $validated['table_id'],
                'table_number' => $table->table_number,
                'amount' => $payment->amount,
                'item_count' => count($validated['items']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'payment_id' => $payment->id,
                'checkout_url' => $checkoutUrl,
                'tx_ref' => $payment->tx_ref,
                'amount' => $payment->amount,
                'calculation' => $orderCalculation,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Walk-In Order Payment Initialize Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while initializing payment',
            ], 500);
        }
    }

    /**
     * Finalize walk-in order upon payment confirmation and send to kitchen.
     */
    public function completeOrder(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();
            if ($payment->order_id && $payment->order) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order already completed',
                    'order' => $payment->order->load('orderItems.menuItem'),
                    'payment' => new PaymentResource($payment),
                ]);
            }

            if ($payment->status !== 'verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not been verified',
                ], 400);
            }

            $metadata = $payment->metadata;
            if (!$metadata || !isset($metadata['items']) || !isset($metadata['table_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment metadata',
                ], 400);
            }

            $result = DB::transaction(function () use ($payment, $metadata) {
                $calculation = $metadata['calculation'];
                $tableId = $metadata['table_id'] ?? null;
                $table = $tableId ? RestaurantTable::withoutGlobalScopes()->find($tableId) : null;
                $hotelId = $payment->hotel_id
                    ?? $table?->hotel_id
                    ?? ($metadata['hotel_id'] ?? null)
                    ?? TenantContext::id()
                    ?? Hotel::value('id');

                if ($table && empty($table->hotel_id) && $hotelId) {
                    $table->update(['hotel_id' => $hotelId]);
                }

                if ($hotelId) {
                    app(TenantContext::class)->setHotelId($hotelId);
                }

                $order = Order::create([
                    'hotel_id' => $hotelId,
                    'order_number' => Order::generateOrderNumber($hotelId),
                    'room_id' => null,
                    'guest_id' => $payment->guest_id ?? ($metadata['guest_id'] ?? null),
                    'reservation_id' => null,
                    'table_id' => $metadata['table_id'],
                    'order_type' => Order::TYPE_WALK_IN,
                    'order_time' => now(),
                    'total' => $calculation['total'],
                    'subtotal' => $calculation['subtotal'],
                    'taxable_amount' => $calculation['subtotal'],
                    'tax' => $calculation['tax'] ?? 0,
                    'service_charge_amount' => $calculation['service_charge'] ?? 0,
                    'discount' => $calculation['discount'] ?? 0,
                    'status' => Order::STATUS_PENDING,
                    'payment_type' => 'card',
                    'notes' => $metadata['special_requests'] ?? null,
                ]);

                foreach ($metadata['items'] as $item) {
                    $menuItem = MenuItem::withoutGlobalScopes()->with('taxRate')->find($item['menu_item_id'] ?? null);
                    $itemName = $menuItem ? $menuItem->name : ($item['name'] ?? 'Menu Item');
                    $itemPrice = $menuItem ? (float) $menuItem->price : (float) ($item['price'] ?? 0);
                    $itemQty = max(1, (int) ($item['quantity'] ?? 1));
                    $taxRate = $menuItem?->taxRate;
                    $rate = ($taxRate && $taxRate->is_active) ? (float) $taxRate->rate : 0.0;
                    $taxIncluded = (bool) ($menuItem?->tax_included ?? false);

                    if ($rate > 0) {
                        if ($taxIncluded) {
                            $baseUnit = round($itemPrice / (1 + ($rate / 100)), 4);
                            $lineSubtotal = round($baseUnit * $itemQty, 2);
                            $lineTotal = round($itemPrice * $itemQty, 2);
                            $taxAmount = round($lineTotal - $lineSubtotal, 2);
                        } else {
                            $lineSubtotal = round($itemPrice * $itemQty, 2);
                            $taxAmount = round($lineSubtotal * ($rate / 100), 2);
                            $lineTotal = round($lineSubtotal + $taxAmount, 2);
                        }
                    } else {
                        $lineSubtotal = round($itemPrice * $itemQty, 2);
                        $taxAmount = 0.0;
                        $lineTotal = $lineSubtotal;
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $item['menu_item_id'],
                        'item_name' => $itemName,
                        'quantity' => $itemQty,
                        'item_price_at_order' => $itemPrice,
                        'tax_rate_id' => $rate > 0 ? $menuItem?->tax_rate_id : null,
                        'tax_rate' => $rate,
                        'tax_amount' => $taxAmount,
                        'subtotal' => $lineSubtotal,
                        'total' => $lineTotal,
                        'line_total' => $lineTotal,
                    ]);
                }

                $payment->update([
                    'order_id' => $order->id,
                ]);

                RestaurantTable::withoutGlobalScopes()->where('id', $metadata['table_id'])->update([
                    'status' => RestaurantTable::STATUS_OCCUPIED,
                ]);

                return [
                    'success' => true,
                    'order' => $order->load('orderItems.menuItem', 'table'),
                ];
            });

            Log::info('Walk-In Order Completed After Payment', [
                'payment_id' => $payment->id,
                'order_id' => $result['order']->id,
                'table_id' => $metadata['table_id'],
            ]);

            try {
                $targetHotelId = $result['order']->hotel_id;
                $chefs = User::where('role', 'chef')
                    ->when($targetHotelId, function ($q) use ($targetHotelId) {
                        $q->where(function ($sub) use ($targetHotelId) {
                            $sub->whereHas('hotelMemberships', fn ($hq) => $hq->where('hotel_id', $targetHotelId))
                                ->orDoesntHave('hotelMemberships');
                        });
                    })
                    ->get();

                foreach ($chefs as $chef) {
                    Notification::create([
                        'user_id' => $chef->id,
                        'type' => 'order_created',
                        'title' => 'New Table Order',
                        'message' => 'Table order #' . $result['order']->order_number . ' has been received for processing.',
                        'read' => false,
                    ]);
                }
            } catch (Throwable $notifyErr) {
                Log::warning('Failed to notify chefs for walk-in order: ' . $notifyErr->getMessage());
            }

            try {
                app(AutomaticWaiterAssignmentService::class)->assignWaiterToReadyOrder($result['order']);
            } catch (Throwable $assignErr) {
                Log::warning('Automatic waiter assignment for walk-in order failed: ' . $assignErr->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully and sent to kitchen',
                'order' => $result['order'],
                'payment' => new PaymentResource($payment->fresh()->load('order')),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Complete Walk-In Order Exception', [
                'message' => $e->getMessage(),
                'tx_ref' => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    /**
     * Calculate walk-in order total without N+1 queries.
     */
    private function calculateOrderTotal(array $items): array
    {
        try {
            $itemIds = array_column($items, 'menu_item_id');
            $menuItems = MenuItem::whereIn('id', $itemIds)->get()->keyBy('id');

            $subtotal = 0.0;
            $itemDetails = [];

            foreach ($items as $item) {
                $menuItem = $menuItems->get($item['menu_item_id']);
                if (!$menuItem) {
                    throw new ModelNotFoundException("Menu item {$item['menu_item_id']} not found.");
                }

                $quantity = (int) $item['quantity'];
                $price = (float) $menuItem->price;
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;

                $itemDetails[] = [
                    'menu_item_id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'price' => $price,
                    'quantity' => $quantity,
                    'total' => $itemTotal,
                ];
            }

            $tax = $subtotal * 0.15;
            $serviceCharge = $subtotal * 0.10;
            $discount = 0.0;
            $total = $subtotal + $tax + $serviceCharge - $discount;

            return [
                'success' => true,
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'service_charge' => round($serviceCharge, 2),
                'discount' => round($discount, 2),
                'total' => round($total, 2),
                'items' => $itemDetails,
            ];
        } catch (Throwable $e) {
            Log::error('Calculate Walk-In Order Total Exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error calculating order total: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get order details by Chapa transaction reference.
     */
    public function getOrderByPayment(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)
                ->with('order.orderItems.menuItem', 'order.table')
                ->firstOrFail();

            if (!$payment->order) {
                return response()->json([
                    'success' => false,
                    'message' => 'No order linked to this payment',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'order' => $payment->order,
                'payment' => new PaymentResource($payment),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Get Order By Payment Exception', [
                'message' => $e->getMessage(),
                'tx_ref' => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }
}
