<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitializePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Mail\ReservationConfirmed;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ChapaService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        protected ChapaService $chapa,
        protected PaymentService $paymentService
    ) {}

    /**
     * Initialize Chapa payment session.
     */
    public function initialize(InitializePaymentRequest $request): JsonResponse
    {
        try {
            $hotelId = TenantContext::id()
                ?: Auth::user()?->hotel_id
                ?: ($request->metadata['hotel_id'] ?? null)
                ?: $request->header('X-Hotel-ID');

            if ($hotelId) {
                $hotel = Hotel::find($hotelId);
                if (!$hotel || !$hotel->isActive()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or inactive hotel selected for payment.',
                    ], 422);
                }
            }

            $txRef = $this->chapa->generateTransactionReference();
            $metadata = $request->metadata ?? [];
            if ($hotelId && empty($metadata['hotel_id'])) {
                $metadata['hotel_id'] = $hotelId;
            }

            $payment = Payment::create([
                'hotel_id' => $hotelId,
                'tx_ref' => $txRef,
                'amount' => $request->amount,
                'currency' => 'ETB',
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'payment_provider' => Payment::PROVIDER_CHAPA,
                'status' => Payment::STATUS_PENDING,
                'metadata' => $metadata,
            ]);

            $response = $this->chapa->initialize([
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'email' => $payment->email,
                'first_name' => $payment->first_name,
                'last_name' => $payment->last_name,
                'phone' => $payment->phone,
                'tx_ref' => $payment->tx_ref,
                'callback_url' => config('chapa.callback_url'),
                'return_url' => config('chapa.return_url') . '?tx_ref=' . urlencode($payment->tx_ref),
                'title' => $request->title ?? 'Hotel Management System Payment',
                'description' => $request->description ?? 'Secure Payment Processing',
            ]);

            if (!$response['success']) {
                Log::error('Chapa Initialize Failed', [
                    'tx_ref' => $txRef,
                    'error' => $response['message'] ?? 'Unknown error',
                    'amount' => $payment->amount,
                ]);

                $payment->markAsFailed($response);

                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Unable to initialize payment',
                    'error' => 'PAYMENT_INIT_FAILED',
                ], 400);
            }

            $checkoutUrl = $this->chapa->getCheckoutUrl($response);
            $payment->markAsInitialized($checkoutUrl);

            Log::info('Payment Initialized Successfully', [
                'payment_id' => $payment->id,
                'tx_ref' => $txRef,
                'amount' => $payment->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'payment_id' => $payment->id,
                'checkout_url' => $checkoutUrl,
                'tx_ref' => $payment->tx_ref,
                'amount' => $payment->amount,
            ]);
        } catch (Throwable $e) {
            Log::error('Payment Initialize Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initializing payment',
                'error' => 'EXCEPTION',
            ], 500);
        }
    }

    /**
     * Verify payment status with Chapa gateway and trigger downstream fulfillment.
     */
    public function verify(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();
            $response = $this->chapa->verify($txRef);

            if (!$response['success']) {
                Log::error('Chapa Verification Failed', [
                    'tx_ref' => $txRef,
                    'error' => $response['message'] ?? 'Unknown error',
                    'payment' => $payment->id,
                ]);

                $payment->markAsFailed($response);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'status' => $payment->status,
                ], 400);
            }

            if ($this->chapa->isSuccessful($response)) {
                $payment->markAsPaid($this->chapa->getTransactionId($response));
                $payment->markAsVerified($response);
                $payment->update([
                    'payment_method' => $this->chapa->getPaymentMethod($response),
                ]);

                Log::info('Payment Verified Successfully', [
                    'payment_id' => $payment->id,
                    'tx_ref' => $txRef,
                    'amount' => $payment->amount,
                ]);

                $type = $payment->metadata['type'] ?? null;
                if ($type === 'reservation') {
                    $this->fulfillReservationPayment($payment, $txRef);
                } elseif ($type === 'order') {
                    $this->fulfillOrderPayment($payment, $txRef);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'status' => $payment->fresh()->status,
                    'payment' => new PaymentResource($payment->fresh()),
                ]);
            }

            $payment->markAsFailed($response);

            return response()->json([
                'success' => false,
                'message' => 'Payment was not completed successfully',
                'status' => $payment->status,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found',
                'error' => 'NOT_FOUND',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Payment Verify Exception', [
                'message' => $e->getMessage(),
                'tx_ref' => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification',
                'error' => 'EXCEPTION',
            ], 500);
        }
    }

    /**
     * Webhook callback endpoint from Chapa.
     */
    public function callback(Request $request): JsonResponse
    {
        try {
            $txRef = (string) $request->get('tx_ref');
            if (!$txRef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing transaction reference',
                ], 400);
            }

            return $this->verify($txRef);
        } catch (Throwable $e) {
            Log::error('Chapa Callback Exception', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred processing callback',
            ], 500);
        }
    }

    /**
     * Get payment status by ID.
     */
    public function getStatus(string $paymentId): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            return response()->json([
                'success' => true,
                'payment' => new PaymentResource($payment),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Get Payment Status Exception', [
                'message' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get payment record by transaction reference.
     */
    public function getByTransactionRef(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            return response()->json([
                'success' => true,
                'payment' => new PaymentResource($payment),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Get Payment By TxRef Exception', [
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
     * List payment history with filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Payment::query();

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('provider')) {
                $query->where('payment_provider', $request->get('provider'));
            }

            if ($request->has('from_date') && $request->has('to_date')) {
                $query->whereBetween('created_at', [
                    $request->get('from_date'),
                    $request->get('to_date'),
                ]);
            }

            if ($request->has('email')) {
                $query->where('email', 'like', '%' . $request->get('email') . '%');
            }

            $perPage = (int) $request->get('per_page', 15);
            $payments = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => PaymentResource::collection($payments),
                'meta' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('List Payments Exception', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred retrieving payments',
            ], 500);
        }
    }

    /**
     * Fulfill reservation creation upon verified payment.
     */
    private function fulfillReservationPayment(Payment $payment, string $txRef): void
    {
        try {
            $hotelId = $payment->hotel_id ?? ($payment->metadata['hotel_id'] ?? null);

            $existingReservation = null;
            if (!empty($payment->reservation_id)) {
                $existingReservation = Reservation::withoutGlobalScopes()->find($payment->reservation_id);
            }

            if (!$existingReservation && !empty($payment->metadata['room_id'])) {
                $existingReservation = Reservation::withoutGlobalScopes()
                    ->where('hotel_id', $hotelId)
                    ->where('guest_id', $payment->guest_id)
                    ->where('room_id', $payment->metadata['room_id'])
                    ->where('check_in_date', $payment->metadata['check_in_date'])
                    ->where('check_out_date', $payment->metadata['check_out_date'])
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->first();
            }

            if ($existingReservation) {
                if ($payment->reservation_id !== $existingReservation->id) {
                    $payment->update(['reservation_id' => $existingReservation->id]);
                }
                return;
            }

            if ($hotelId && !empty($payment->metadata['room_id'])) {
                $roomBelongs = Room::where('hotel_id', $hotelId)
                    ->where('id', $payment->metadata['room_id'])
                    ->exists();
                if (!$roomBelongs) {
                    throw new Exception("Room {$payment->metadata['room_id']} does not belong to hotel {$hotelId}");
                }
            }

            $reservation = Reservation::create([
                'hotel_id' => $hotelId,
                'booking_reference' => Reservation::generateBookingReference(),
                'guest_id' => $payment->guest_id,
                'room_id' => $payment->metadata['room_id'],
                'check_in_date' => $payment->metadata['check_in_date'],
                'check_out_date' => $payment->metadata['check_out_date'],
                'number_of_guests' => $payment->metadata['number_of_guests'],
                'total_amount' => $payment->amount,
                'status' => 'confirmed',
                'special_requests' => $payment->metadata['special_requests'] ?? null,
                'created_by' => null,
            ]);

            $payment->update(['reservation_id' => $reservation->id]);

            $reservation->load(['room.roomType', 'guest']);
            $guestEmail = $reservation->guest?->email ?? ($payment->metadata['email'] ?? null);
            if ($guestEmail) {
                try {
                    Mail::to($guestEmail)->send(new ReservationConfirmed($reservation));
                } catch (Throwable $mailEx) {
                    Log::error('Failed to send confirmation email after payment: ' . $mailEx->getMessage());
                }
            }
        } catch (Throwable $e) {
            Log::error('Failed to create reservation after payment verification', [
                'payment_id' => $payment->id,
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fulfill room-service order creation upon verified payment.
     */
    private function fulfillOrderPayment(Payment $payment, string $txRef): void
    {
        try {

            $existingOrderId = $payment->order_id ?? ($payment->metadata['order_id'] ?? null);
            if ($existingOrderId) {
                $order = Order::withoutGlobalScopes()->find($existingOrderId);
                if ($order) {
                    if (!$payment->order_id) {
                        $payment->update(['order_id' => $order->id]);
                    }

                    $order->update([
                        'payment_type' => 'chapa',
                    ]);

                    Log::info('Existing order marked as paid after Chapa verification', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'tx_ref' => $txRef,
                    ]);

                    event(new \App\Events\PaymentStatusUpdated($order, 'paid'));
                    event(new \App\Events\OrderStatusUpdated($order, $order->status, 'paid'));
                    return;
                }
            }

            if ($payment->order_id) {
                return;
            }

            $calculation = $payment->metadata['calculation'] ?? [];
            $orderItems = $payment->metadata['items'] ?? [];
            $roomId = $payment->metadata['room_id'] ?? null;
            $notes = $payment->metadata['notes'] ?? null;

            if (empty($orderItems)) {
                throw new Exception('No order items found in payment metadata');
            }

            $hotelId = $payment->hotel_id ?? ($payment->metadata['hotel_id'] ?? null);

            $order = Order::create([
                'hotel_id' => $hotelId,
                'order_number' => Order::generateOrderNumber($hotelId),
                'reservation_id' => null,
                'guest_id' => $payment->guest_id,
                'room_id' => $roomId,
                'order_time' => now(),
                'status' => Order::STATUS_PENDING,
                'payment_type' => 'card',
                'subtotal' => $calculation['subtotal'] ?? $payment->amount,
                'tax' => $calculation['tax'] ?? 0,
                'discount' => $calculation['discount'] ?? 0,
                'total' => $payment->amount,
                'notes' => $notes,
            ]);

            foreach ($orderItems as $item) {
                $itemPrice = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $lineTotal = $itemPrice * $quantity;

                $order->orderItems()->create([
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $quantity,
                    'item_price_at_order' => $itemPrice,
                    'line_total' => $lineTotal,
                    'notes' => $item['special_instructions'] ?? null,
                ]);
            }

            $payment->update(['order_id' => $order->id]);
        } catch (Throwable $e) {
            Log::error('Failed to create order after payment verification', [
                'payment_id' => $payment->id,
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

