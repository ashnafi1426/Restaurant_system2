<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Guest;
use App\Models\Room;
use App\Services\PaymentService;
use App\Services\ChapaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * GuestOrderPaymentController
 * ============================================================================
 * Handles payment flow for guest QR food orders
 * 
 * Payment Flow:
 * 1. Guest scans room QR code
 * 2. Guest selects menu items and adds to cart
 * 3. Guest initiates checkout with payment
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
class GuestOrderPaymentController extends Controller
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
     * Initialize Order Payment
     * ============================================================================
     * Validates order items and initializes payment with Chapa
     * 
     * Request Body:
     * {
     *   "guest_id": "uuid",
     *   "room_id": "uuid",
     *   "items": [
     *     {
     *       "menu_item_id": "uuid",
     *       "quantity": 2,
     *       "special_instructions": "No onions"
     *     },
     *     ...
     *   ],
     *   "notes": "Optional order notes",
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
                'guest_id'     => 'required|uuid|exists:guests,id',
                'room_id'      => 'required|uuid|exists:rooms,id',
                'items'        => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|uuid|exists:menu_items,id',
                'items.*.quantity'     => 'required|integer|min:1|max:100',
                'items.*.special_instructions' => 'nullable|string',
                'notes'        => 'nullable|string',
                'first_name'   => 'required|string|max:255',
                'last_name'    => 'required|string|max:255',
                'email'        => 'required|email',
                'phone'        => 'required|string|max:20',
            ]);

            // Get guest and room
            $guest = Guest::findOrFail($validated['guest_id']);
            $room = Room::findOrFail($validated['room_id']);

            // Calculate order total
            $orderCalculation = $this->calculateOrderTotal($validated['items']);

            if (!$orderCalculation['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $orderCalculation['message'],
                ], 400);
            }

            // Create payment record
            $payment = $this->paymentService->createOrderPayment([
                'amount'    => $orderCalculation['total'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email'     => $validated['email'],
                'phone'     => $validated['phone'],
                'guest_id'  => $validated['guest_id'],
                'room_id'   => $validated['room_id'],
                'metadata'  => [
                    'type'      => 'order',
                    'room_id'   => $validated['room_id'],
                    'items'     => $validated['items'],
                    'notes'     => $validated['notes'] ?? null,
                    'calculation' => $orderCalculation,
                ],
            ]);

            // Initialize payment with Chapa
            $chapaResponse = $this->chapaService->initialize([
                'amount'       => $payment->amount,
                'currency'     => 'ETB',
                'email'        => $payment->email,
                'first_name'   => $payment->first_name,
                'last_name'    => $payment->last_name,
                'phone'        => $payment->phone,
                'tx_ref'       => $payment->tx_ref,
                'callback_url' => config('chapa.callback_url'),
                'return_url'   => config('chapa.return_url'),
                'title'        => 'Guest Order Payment',
                'description'  => sprintf(
                    'Order for Room %s - %d items',
                    $room->room_number,
                    count($validated['items'])
                ),
            ]);

            // Handle initialization failure
            if (!$chapaResponse['success']) {
                Log::error('Chapa Initialize Failed for Order', [
                    'payment_id' => $payment->id,
                    'error'      => $chapaResponse['message'] ?? 'Unknown error',
                ]);

                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment',
                ], 400);
            }

            // Update payment with checkout URL
            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            $payment->markAsInitialized($checkoutUrl);

            Log::info('Order Payment Initialized', [
                'payment_id'  => $payment->id,
                'guest_id'    => $validated['guest_id'],
                'room_id'     => $validated['room_id'],
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
            Log::error('Order Payment Initialize Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    /**
     * ============================================================================
     * Complete Order After Payment
     * ============================================================================
     * Called after payment verification
     * Creates the actual order record in database
     * Chef dashboard will receive the order
     * 
     * Only called by PaymentController after payment is verified
     * 
     * @param string $txRef - Transaction reference
     * @return JsonResponse
     */
    public function completeOrder(string $txRef): JsonResponse
    {
        try {
            // Find payment
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            // Verify payment is verified
            if (!$payment->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not been verified',
                ], 400);
            }

            // Get metadata
            $metadata = $payment->metadata;

            if (!$metadata || !isset($metadata['items'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment metadata',
                ], 400);
            }

            // Prepare order data
            $calculation = $metadata['calculation'];
            $orderItems = $metadata['items'];

            // Create order
            $result = $this->paymentService->handleOrderPaymentSuccess(
                $payment,
                [
                    'guest_id'  => $payment->guest_id,
                    'room_id'   => $metadata['room_id'] ?? null,
                    'subtotal'  => $calculation['subtotal'],
                    'tax'       => $calculation['tax'],
                    'discount'  => $calculation['discount'],
                    'notes'     => $metadata['notes'] ?? null,
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
                'payment_id'  => $payment->id,
                'order_id'    => $result['order']->id,
                'guest_id'    => $payment->guest_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully and sent to kitchen',
                'order'   => $result['order'],
                'payment' => new PaymentResource($payment),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Complete Order Exception', [
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
     * ============================================================================
     * Calculate Order Total
     * ============================================================================
     * Calculates total price for order items
     * 
     * @param array $items - Array of order items with menu_item_id and quantity
     * 
     * @return array - Price breakdown or error
     */
    private function calculateOrderTotal(array $items): array
    {
        try {
            $subtotal = 0;
            $itemDetails = [];

            // Calculate subtotal from menu items
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

            // Apply discount (if any - can be extended)
            $discount = 0;

            // Calculate total
            $total = $subtotal + $tax - $discount;

            return [
                'success'   => true,
                'subtotal'  => (float) $subtotal,
                'tax'       => (float) $tax,
                'discount'  => (float) $discount,
                'total'     => (float) $total,
                'items'     => $itemDetails,
            ];

        } catch (\Exception $e) {
            Log::error('Calculate Order Total Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error calculating order total: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ============================================================================
     * Get Order by Payment
     * ============================================================================
     * Retrieve order linked to a payment
     * 
     * @param string $txRef - Transaction reference
     * @return JsonResponse
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
                'order'   => $payment->order,
                'payment' => new PaymentResource($payment),
            ]);

        } catch (\Exception $e) {
            Log::error('Get Order By Payment Exception', [
                'message' => $e->getMessage(),
                'tx_ref'  => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }
}
