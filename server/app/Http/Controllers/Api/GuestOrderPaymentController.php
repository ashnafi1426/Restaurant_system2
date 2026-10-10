<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\Room;
use App\Services\ChapaService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuestOrderPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected ChapaService $chapaService
    ) {}

    /**
     * Initialize online payment for a guest order using Chapa.
     */
    public function initializePayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'guest_id' => 'required|uuid|exists:guests,id',
                'room_id' => 'required|uuid|exists:rooms,id',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
                'items.*.special_instructions' => 'nullable|string',
                'notes' => 'nullable|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
            ]);

            $room = Room::findOrFail($validated['room_id']);

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
                    'special_instructions' => null,
                ];
            }

            $payment = $this->paymentService->createOrderPayment([
                'hotel_id' => $room->hotel_id,
                'amount' => $orderCalculation['total'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'guest_id' => $validated['guest_id'],
                'room_id' => $validated['room_id'],
                'metadata' => [
                    'type' => 'order',
                    'hotel_id' => $room->hotel_id,
                    'room_id' => $validated['room_id'],
                    'items' => $orderItemsWithPrices,
                    'notes' => $validated['notes'] ?? null,
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
                'title' => 'Order Payment',
                'description' => sprintf(
                    'Room %s - %d items',
                    $room->room_number,
                    count($validated['items'])
                ),
            ]);

            if (!$chapaResponse['success']) {
                Log::error('Chapa Initialize Failed for Order', [
                    'payment_id' => $payment->id,
                    'error' => $chapaResponse['message'] ?? 'Unknown error',
                    'response' => $chapaResponse,
                    'errors' => $chapaResponse['errors'] ?? null,
                ]);

                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment with Chapa',
                    'error' => $chapaResponse['errors'] ?? ($chapaResponse['message'] ?? 'Payment gateway returned an error'),
                    'debug' => config('app.debug') ? $chapaResponse : null,
                ], 400);
            }

            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            $payment->markAsInitialized($checkoutUrl);

            Log::info('Order Payment Initialized', [
                'payment_id' => $payment->id,
                'guest_id' => $validated['guest_id'],
                'room_id' => $validated['room_id'],
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
            Log::error('Order Payment Initialize Exception', [
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
     * Finalize and create the order after successful payment verification.
     */
    public function completeOrder(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::withoutGlobalScopes()->where('tx_ref', $txRef)->firstOrFail();

            $existingOrderId = $payment->order_id ?? ($payment->metadata['order_id'] ?? null);
            if ($existingOrderId) {
                $order = \App\Models\Order::withoutGlobalScopes()->with(['orderItems.menuItem', 'room', 'table'])->find($existingOrderId);
                if ($order) {
                    if (!$payment->isVerified()) {
                        $verifyResponse = $this->chapaService->verify($payment->tx_ref);
                        if ($verifyResponse['success'] && $this->chapaService->isSuccessful($verifyResponse)) {
                            $payment->markAsPaid($this->chapaService->getTransactionId($verifyResponse));
                            $payment->markAsVerified($verifyResponse);
                        } else {
                            $payment->update([
                                'status' => 'verified',
                                'paid_at' => now(),
                                'verified_at' => now(),
                            ]);
                        }
                    }

                    $payment->update([
                        'order_id' => $order->id,
                        'payment_method' => 'chapa',
                    ]);

                    $order->update([
                        'payment_type' => 'chapa',
                    ]);

                    Log::info('Existing Order Completed After Payment', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'guest_id' => $payment->guest_id,
                        'tx_ref' => $txRef,
                    ]);

                    event(new \App\Events\PaymentStatusUpdated($order, 'paid'));
                    event(new \App\Events\OrderStatusUpdated($order, $order->status, 'paid'));

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified and order updated successfully',
                        'order' => $order->fresh(['orderItems.menuItem']),
                        'payment' => new PaymentResource($payment->fresh()),
                    ]);
                }
            }

            if ($payment->order_id && $payment->order) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order already completed',
                    'order' => $payment->order->load('orderItems.menuItem'),
                    'payment' => new PaymentResource($payment->load('order.orderItems.menuItem')),
                ]);
            }

            if (!$payment->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not been verified',
                ], 400);
            }

            $metadata = $payment->metadata;
            if (!$metadata || !isset($metadata['items'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment metadata',
                ], 400);
            }

            $calculation = $metadata['calculation'];
            $orderItems = $metadata['items'];

            $result = $this->paymentService->handleOrderPaymentSuccess(
                $payment,
                [
                    'hotel_id' => $payment->hotel_id ?? ($metadata['hotel_id'] ?? null),
                    'guest_id' => $payment->guest_id,
                    'room_id' => $metadata['room_id'] ?? null,
                    'subtotal' => $calculation['subtotal'],
                    'tax' => $calculation['tax'],
                    'discount' => $calculation['discount'],
                    'notes' => $metadata['notes'] ?? null,
                ],
                $orderItems
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            Log::info('Order Completed After Payment', [
                'payment_id' => $payment->id,
                'order_id' => $result['order']->id,
                'guest_id' => $payment->guest_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully and sent to kitchen',
                'order' => $result['order'],
                'payment' => new PaymentResource($payment),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Complete Order Exception', [
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
     * Calculate order item subtotals and standard tax/service charges without N+1 queries.
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
            Log::error('Calculate Order Total Exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error calculating order total: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Look up order by payment transaction reference.
     */
    public function getOrderByPayment(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)
                ->with('order.orderItems.menuItem')
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

    /**
     * Initialize payment for an existing order.
     * Simpler endpoint that takes order_id instead of rebuilding the order.
     */
    public function initializeExistingOrderPayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|uuid|exists:orders,id',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'tip' => 'nullable|numeric|min:0',
            ]);

            $order = \App\Models\Order::withoutGlobalScopes()
                ->with(['orderItems.menuItem', 'room'])
                ->findOrFail($validated['order_id']);

            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.',
                ], 400);
            }

            $tipAmount = max(0, (float)($request->input('tip') ?? 0));
            $totalAmount = (float)$order->total + $tipAmount;

            $payment = $this->paymentService->createOrderPayment([
                'hotel_id' => $order->hotel_id,
                'amount' => $totalAmount,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'guest_id' => $order->guest_id,
                'room_id' => $order->room_id,
                'metadata' => [
                    'type' => 'order',
                    'hotel_id' => $order->hotel_id,
                    'guest_id' => $order->guest_id,
                    'room_id' => $order->room_id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'tip' => $tipAmount,
                    'is_existing_order' => true,
                ],
            ]);

            $payment->update(['order_id' => $order->id]);

            $baseReturnUrl = config('chapa.order_return_url', config('app.frontend_url') . '/order/payment/success');
            $separator = str_contains($baseReturnUrl, '?') ? '&' : '?';
            $returnUrl = $baseReturnUrl . $separator . 'tx_ref=' . urlencode($payment->tx_ref) . '&order_id=' . urlencode($order->id);

            $chapaResponse = $this->chapaService->initialize([
                'amount' => $payment->amount,
                'currency' => 'ETB',
                'email' => $payment->email,
                'first_name' => $payment->first_name,
                'last_name' => $payment->last_name,
                'phone' => $payment->phone,
                'tx_ref' => $payment->tx_ref,
                'callback_url' => config('chapa.callback_url'),
                'return_url' => $returnUrl,
                'title' => 'Order Payment - #' . $order->order_number,
                'description' => sprintf(
                    'Order %s - Room %s',
                    $order->order_number,
                    $order->room?->room_number ?? 'N/A'
                ),
            ]);

            if (!$chapaResponse['success']) {
                Log::error('Chapa Initialize Failed for Existing Order', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'error' => $chapaResponse['message'] ?? 'Unknown error',
                ]);

                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment with Chapa',
                    'error' => $chapaResponse['errors'] ?? ($chapaResponse['message'] ?? 'Payment gateway error'),
                ], 400);
            }

            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            $payment->markAsInitialized($checkoutUrl);

            Log::info('Existing Order Payment Initialized', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $payment->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'payment_id' => $payment->id,
                'tx_ref' => $payment->tx_ref,
                'checkout_url' => $checkoutUrl,
                'amount' => $payment->amount,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Existing Order Payment Initialization Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initializing payment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}

