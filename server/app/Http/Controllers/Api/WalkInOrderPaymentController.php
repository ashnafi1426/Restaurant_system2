<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Services\PaymentService;
use App\Services\ChapaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * WalkInOrderPaymentController
 * ============================================================================
 * Handles payment flow for walk-in restaurant table orders
 * 
 * Payment Flow:
 * 1. Customer scans table QR code
 * 2. Customer selects menu items and adds to cart
 * 3. Customer initiates checkout with payment
 * 4. Initialize payment with Chapa
 * 5. Redirect to Chapa checkout
 * 6. Customer completes payment
 * 7. Verify payment status
 * 8. Create order record ONLY after payment verification
 * 9. Send order to kitchen
 * 10. Chef cannot see order until payment is verified
 * 
 * Order is NEVER created before successful payment verification
 * ============================================================================
 */
class WalkInOrderPaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected ChapaService $chapaService;

    public function __construct(
        PaymentService $paymentService,
        ChapaService $chapaService
    ) {
        $this->paymentService = $paymentService;
        $this->chapaService = $chapaService;
    }

    /**
     * ============================================================================
     * Initialize Walk-In Order Payment
     * ============================================================================
     * Validates order items and initializes payment with Chapa
     * 
     * Request Body:
     * {
     *   "table_id": "uuid",
     *   "qr_token": "table-2-GveD6NRGFa",
     *   "items": [
     *     {
     *       "menu_item_id": "uuid",
     *       "quantity": 2
     *     }
     *   ],
     *   "special_requests": "Optional order notes",
     *   "first_name": "John",
     *   "last_name": "Doe",
     *   "email": "john@example.com",
     *   "phone": "+251912345678"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function initializePayment(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'table_id'     => 'required|uuid|exists:restaurant_tables,id',
                'qr_token'     => 'required|string',
                'items'        => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity'     => 'required|integer|min:1|max:100',
                'special_requests' => 'nullable|string',
                'first_name'   => 'required|string|max:255',
                'last_name'    => 'required|string|max:255',
                'email'        => 'required|email',
                'phone'        => 'required|string|max:20',
            ]);

            // Get table
            $table = RestaurantTable::findOrFail($validated['table_id']);

            // Calculate order total
            $orderCalculation = $this->calculateOrderTotal($validated['items']);

            if (!$orderCalculation['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $orderCalculation['message'],
                ], 400);
            }

            // Prepare order items with prices for payment metadata
            $orderItemsWithPrices = [];
            foreach ($orderCalculation['items'] as $item) {
                $orderItemsWithPrices[] = [
                    'menu_item_id' => $item['menu_item_id'],
                    'name'         => $item['name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'total'        => $item['total'],
                ];
            }

            // Create payment record
            $payment = Payment::create([
                'id'         => Str::uuid(),
                'tx_ref'     => 'WALKIN-' . strtoupper(Str::random(12)),
                'amount'     => $orderCalculation['total'],
                'currency'   => 'ETB',
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'phone'      => $validated['phone'],
                'status'     => 'pending',
                'payment_method' => 'chapa',
                'metadata' => [
                    'type'             => 'walk_in_order',
                    'table_id'         => $validated['table_id'],
                    'qr_token'         => $validated['qr_token'],
                    'items'            => $orderItemsWithPrices,
                    'special_requests' => $validated['special_requests'] ?? null,
                    'calculation'      => $orderCalculation,
                ],
            ]);

            // Initialize payment with Chapa
            $title = 'Table Order'; // 11 characters - safe for Chapa
            
            $chapaResponse = $this->chapaService->initialize([
                'amount'       => $payment->amount,
                'currency'     => 'ETB',
                'email'        => $payment->email,
                'first_name'   => $payment->first_name,
                'last_name'    => $payment->last_name,
                'phone'        => $payment->phone,
                'tx_ref'       => $payment->tx_ref,
                'callback_url' => config('chapa.callback_url'),
                'return_url'   => config('chapa.order_return_url', config('app.frontend_url') . '/order/payment/success'),
                'title'        => $title,
                'description'  => sprintf(
                    'Table %s - %d items',
                    $table->table_number,
                    count($validated['items'])
                ),
            ]);

            // Handle initialization failure
            if (!$chapaResponse['success']) {
                Log::error('Chapa Initialize Failed for Walk-In Order', [
                    'payment_id' => $payment->id,
                    'error'      => $chapaResponse['message'] ?? 'Unknown error',
                    'response'   => $chapaResponse,
                ]);

                $payment->update([
                    'status' => 'failed',
                    'error_message' => $chapaResponse['message'] ?? 'Payment initialization failed',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment with Chapa',
                    'error'   => $chapaResponse['errors'] ?? ($chapaResponse['message'] ?? 'Payment gateway error'),
                ], 400);
            }

            // Update payment with checkout URL
            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            $payment->update([
                'checkout_url' => $checkoutUrl,
                'status' => 'initialized',
            ]);

            Log::info('Walk-In Order Payment Initialized', [
                'payment_id'  => $payment->id,
                'table_id'    => $validated['table_id'],
                'table_number' => $table->table_number,
                'amount'      => $payment->amount,
                'item_count'  => count($validated['items']),
            ]);

            // Return response
            return response()->json([
                'success'       => true,
                'message'       => 'Payment initialized successfully',
                'payment_id'    => $payment->id,
                'checkout_url'  => $checkoutUrl,
                'tx_ref'        => $payment->tx_ref,
                'amount'        => $payment->amount,
                'calculation'   => $orderCalculation,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Walk-In Order Payment Initialize Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while initializing payment',
            ], 500);
        }
    }

    /**
     * ============================================================================
     * Complete Walk-In Order After Payment
     * ============================================================================
     * Called after payment verification
     * Creates the actual order record in database
     * 
     * @param string $txRef - Transaction reference
     * @return JsonResponse
     */
    public function completeOrder(string $txRef): JsonResponse
    {
        try {
            // Find payment
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            // Check if order already exists
            if ($payment->order_id && $payment->order) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order already completed',
                    'order'   => $payment->order->load('orderItems.menuItem'),
                    'payment' => new PaymentResource($payment),
                ]);
            }

            // Verify payment is verified
            if ($payment->status !== 'verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not been verified',
                ], 400);
            }

            // Get metadata
            $metadata = $payment->metadata;

            if (!$metadata || !isset($metadata['items']) || !isset($metadata['table_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment metadata',
                ], 400);
            }

            // Create order
            $result = DB::transaction(function () use ($payment, $metadata) {
                $calculation = $metadata['calculation'];
                
                // Create order
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'room_id' => null,
                    'guest_id' => null,
                    'reservation_id' => null,
                    'table_id' => $metadata['table_id'],
                    'order_type' => Order::TYPE_WALK_IN,
                    'order_time' => now(),
                    'total' => $calculation['total'],
                    'subtotal' => $calculation['subtotal'],
                    'tax' => $calculation['tax'] ?? 0,
                    'service_charge' => $calculation['service_charge'] ?? 0,
                    'discount' => $calculation['discount'] ?? 0,
                    'status' => Order::STATUS_PENDING,
                    'payment_type' => 'card', // Paid via Chapa
                    'notes' => $metadata['special_requests'] ?? null,
                ]);

                // Create order items
                foreach ($metadata['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'item_price_at_order' => $item['price'],
                        'line_total' => $item['total'],
                    ]);
                }

                // Link payment to order
                $payment->update([
                    'order_id' => $order->id,
                ]);

                // Update table status to occupied
                RestaurantTable::where('id', $metadata['table_id'])->update([
                    'status' => RestaurantTable::STATUS_OCCUPIED,
                ]);

                return [
                    'success' => true,
                    'order' => $order->load('orderItems.menuItem', 'table'),
                ];
            });

            Log::info('Walk-In Order Completed After Payment', [
                'payment_id'  => $payment->id,
                'order_id'    => $result['order']->id,
                'table_id'    => $metadata['table_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully and sent to kitchen',
                'order'   => $result['order'],
                'payment' => new PaymentResource($payment->fresh()->load('order')),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Complete Walk-In Order Exception', [
                'message' => $e->getMessage(),
                'tx_ref'  => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    /**
     * Calculate Order Total
     */
    private function calculateOrderTotal(array $items): array
    {
        try {
            $subtotal = 0;
            $itemDetails = [];

            foreach ($items as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                $itemTotal = (float) $menuItem->price * (int) $item['quantity'];
                $subtotal += $itemTotal;

                $itemDetails[] = [
                    'menu_item_id' => $menuItem->id,
                    'name'         => $menuItem->name,
                    'price'        => (float) $menuItem->price,
                    'quantity'     => (int) $item['quantity'],
                    'total'        => $itemTotal,
                ];
            }

            // Calculate tax (15%)
            $tax = $subtotal * 0.15;

            // Calculate service charge (10%)
            $serviceCharge = $subtotal * 0.10;

            // No discount for walk-in orders
            $discount = 0;

            // Calculate total
            $total = $subtotal + $tax + $serviceCharge - $discount;

            return [
                'success'        => true,
                'subtotal'       => (float) $subtotal,
                'tax'            => (float) $tax,
                'service_charge' => (float) $serviceCharge,
                'discount'       => (float) $discount,
                'total'          => (float) $total,
                'items'          => $itemDetails,
            ];

        } catch (\Exception $e) {
            Log::error('Calculate Walk-In Order Total Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error calculating order total: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Order by Payment
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
                'order'   => $payment->order,
                'payment' => new PaymentResource($payment),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }
}
