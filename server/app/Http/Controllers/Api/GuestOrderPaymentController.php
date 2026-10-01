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

    public function initializePayment(Request $request): JsonResponse
    {
        try {
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

            $guest = Guest::findOrFail($validated['guest_id']);
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
                    'menu_item_id'         => $item['menu_item_id'],
                    'name'                 => $item['name'],
                    'quantity'             => $item['quantity'],
                    'price'                => $item['price'],
                    'total'                => $item['total'],
                    'special_instructions' => null,
                ];
            }

            $payment = $this->paymentService->createOrderPayment([
                'hotel_id'   => $room->hotel_id,
                'amount'     => $orderCalculation['total'],
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'phone'      => $validated['phone'],
                'guest_id'   => $validated['guest_id'],
                'room_id'    => $validated['room_id'],
                'metadata'   => [
                    'type'        => 'order',
                    'hotel_id'    => $room->hotel_id,
                    'room_id'     => $validated['room_id'],
                    'items'       => $orderItemsWithPrices,
                    'notes'       => $validated['notes'] ?? null,
                    'calculation' => $orderCalculation,
                ],
            ]);

            $title = 'Order Payment';
            
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
                    'Room %s - %d items',
                    $room->room_number,
                    count($validated['items'])
                ),
            ]);

            if (!$chapaResponse['success']) {
                Log::error('Chapa Initialize Failed for Order', [
                    'payment_id' => $payment->id,
                    'error'      => $chapaResponse['message'] ?? 'Unknown error',
                    'response'   => $chapaResponse,
                    'errors'     => $chapaResponse['errors'] ?? null,
                ]);

                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment with Chapa',
                    'error'   => $chapaResponse['errors'] ?? ($chapaResponse['message'] ?? 'Payment gateway returned an error'),
                    'debug'   => config('app.debug') ? $chapaResponse : null,
                ], 400);
            }

            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            $payment->markAsInitialized($checkoutUrl);

            Log::info('Order Payment Initialized', [
                'payment_id'  => $payment->id,
                'guest_id'    => $validated['guest_id'],
                'room_id'     => $validated['room_id'],
                'amount'      => $payment->amount,
                'item_count'  => count($validated['items']),
            ]);

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
                'trace'   => $e->getTraceAsString(),
            ]);

            $errorMessage = 'An error occurred while initializing payment';
            $errorDetails = [];
            
            if (config('app.debug')) {
                $errorMessage = $e->getMessage();
                $errorDetails = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'details' => $errorDetails,
            ], 500);
        }
    }

    public function completeOrder(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::withoutGlobalScopes()->where('tx_ref', $txRef)->firstOrFail();

            if ($payment->order_id && $payment->order) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order already completed',
                    'order'   => $payment->order->load('orderItems.menuItem'),
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
                    'hotel_id'  => $payment->hotel_id ?? ($metadata['hotel_id'] ?? null),
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

            $tax = $subtotal * 0.15;

            $serviceCharge = $subtotal * 0.10;

            $discount = 0;

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
            Log::error('Calculate Order Total Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error calculating order total: ' . $e->getMessage(),
            ];
        }
    }

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
