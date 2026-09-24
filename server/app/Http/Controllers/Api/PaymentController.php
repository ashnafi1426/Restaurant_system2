<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitializePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ChapaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\TenantContext;
use App\Models\Hotel;
use App\Models\Room;

class PaymentController extends Controller
{
    protected ChapaService $chapa;

    public function __construct(ChapaService $chapa)
    {
        $this->chapa = $chapa;
    }

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
                        'message' => 'Invalid or inactive hotel selected for payment.'
                    ], 422);
                }
            }
            $txRef = $this->chapa->generateTransactionReference();

            $metadata = $request->metadata ?? [];
            if ($hotelId && empty($metadata['hotel_id'])) {
                $metadata['hotel_id'] = $hotelId;
            }
            $payment = Payment::create([
                'hotel_id'           => $hotelId,
                'tx_ref'             => $txRef,
                'amount'             => $request->amount,
                'currency'           => 'ETB',
                'first_name'         => $request->first_name,
                'last_name'          => $request->last_name,
                'email'              => $request->email,
                'phone'              => $request->phone,
                'payment_provider'   => Payment::PROVIDER_CHAPA,
                'status'             => Payment::STATUS_PENDING,
                'metadata'           => $metadata,
            ]);
            $response = $this->chapa->initialize([
                'amount'        => $payment->amount,
                'currency'      => $payment->currency,
                'email'         => $payment->email,
                'first_name'    => $payment->first_name,
                'last_name'     => $payment->last_name,
                'phone'         => $payment->phone,
                'tx_ref'        => $payment->tx_ref,
                'callback_url'  => config('chapa.callback_url'),
                'return_url'    => config('chapa.return_url') . '?tx_ref=' . urlencode($payment->tx_ref),
                'title'         => $request->title ?? 'Hotel Management System Payment',
                'description'   => $request->description ?? 'Secure Payment Processing',
            ]);
            if (!$response['success']) {
                Log::error('Chapa Initialize Failed', [
                    'tx_ref'  => $txRef,
                    'error'   => $response['message'] ?? 'Unknown error',
                    'amount'  => $payment->amount,
                ]);

                $payment->markAsFailed($response);
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Unable to initialize payment',
                    'error'   => 'PAYMENT_INIT_FAILED',
                ], 400);
            }
            $checkoutUrl = $this->chapa->getCheckoutUrl($response);
            $payment->markAsInitialized($checkoutUrl);
            Log::info('Payment Initialized Successfully', [
                'payment_id' => $payment->id,
                'tx_ref'     => $txRef,
                'amount'     => $payment->amount,
                'email'      => $payment->email,
            ]);
            return response()->json([
                'success'       => true,
                'message'       => 'Payment initialized successfully',
                'payment_id'    => $payment->id,
                'checkout_url'  => $checkoutUrl,
                'tx_ref'        => $payment->tx_ref,
                'amount'        => $payment->amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Initialize Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initializing payment',
                'error'   => 'EXCEPTION',
            ], 500);
        }
    }

    public function verify(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();
            $response = $this->chapa->verify($txRef);

            if (!$response['success']) {
                Log::error('Chapa Verification Failed', [
                    'tx_ref'  => $txRef,
                    'error'   => $response['message'] ?? 'Unknown error',
                    'payment' => $payment->id,
                ]);

                $payment->markAsFailed($response);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'status'  => $payment->status,
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
                    'tx_ref'     => $txRef,
                    'amount'     => $payment->amount,
                    'status'     => $payment->fresh()->status,
                ]);
                if ($payment->metadata && isset($payment->metadata['type']) && $payment->metadata['type'] === 'reservation') {
                    Log::info('Creating reservation after payment verification', [
                        'payment_id' => $payment->id,
                        'tx_ref'     => $txRef,
                    ]);
                    
                    try {
                        $paymentHotelId = $payment->hotel_id ?? ($payment->metadata['hotel_id'] ?? null);

                        if ($paymentHotelId && !empty($payment->metadata['room_id'])) {
                            $roomBelongs = Room::where('hotel_id', $paymentHotelId)
                                ->where('id', $payment->metadata['room_id'])
                                ->exists();
                            if (!$roomBelongs) {
                                throw new \Exception("Room {$payment->metadata['room_id']} does not belong to hotel {$paymentHotelId}");
                            }
                        }

                        $reservation = Reservation::create([
                            'hotel_id'          => $paymentHotelId,
                            'booking_reference' => Reservation::generateBookingReference(),
                            'guest_id'          => $payment->guest_id,
                            'room_id'           => $payment->metadata['room_id'],
                            'check_in_date'     => $payment->metadata['check_in_date'],
                            'check_out_date'    => $payment->metadata['check_out_date'],
                            'number_of_guests'  => $payment->metadata['number_of_guests'],
                            'total_amount'      => $payment->amount,
                            'status'            => 'pending',
                            'special_requests'  => $payment->metadata['special_requests'] ?? null,
                            'created_by'        => null,
                        ]);
                        
                        $payment->update(['reservation_id' => $reservation->id]);
                        
                        Log::info('Reservation created successfully after payment', [
                            'payment_id'       => $payment->id,
                            'reservation_id'   => $reservation->id,
                            'hotel_id'         => $reservation->hotel_id,
                            'booking_reference' => $reservation->booking_reference,
                            'status'           => $reservation->status,
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('Failed to create reservation after payment verification', [
                            'payment_id' => $payment->id,
                            'tx_ref'     => $txRef,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
                if ($payment->metadata && isset($payment->metadata['type']) && $payment->metadata['type'] === 'order') {
                    Log::info('🍽️ [ORDER] Creating order after payment verification', [
                        'payment_id' => $payment->id,
                        'tx_ref'     => $txRef,
                        'metadata'   => $payment->metadata,
                    ]);

                    if ($payment->order_id) {
                        Log::info(' [ORDER] Order already exists for verified payment, skipping duplicate creation', [
                            'payment_id' => $payment->id,
                            'order_id'   => $payment->order_id,
                            'tx_ref'     => $txRef,
                        ]);
                    } else {
                        try {
                            $calculation = $payment->metadata['calculation'] ?? [];
                            $orderItems = $payment->metadata['items'] ?? [];
                            $roomId = $payment->metadata['room_id'] ?? null;
                            $notes = $payment->metadata['notes'] ?? null;

                            Log::info('📋 [ORDER] Order data extracted', [
                                'calculation' => $calculation,
                                'items_count' => count($orderItems),
                                'room_id'     => $roomId,
                                'guest_id'    => $payment->guest_id,
                            ]);

                            if (empty($orderItems)) {
                                throw new \Exception('No order items found in payment metadata');
                            }

                            $order = Order::create([
                                'hotel_id'         => $payment->hotel_id ?? ($payment->metadata['hotel_id'] ?? null),
                                'order_number'     => Order::generateOrderNumber(),
                                'reservation_id'   => null,
                                'guest_id'         => $payment->guest_id,
                                'room_id'          => $roomId,
                                'order_time'       => now(),
                                'status'           => Order::STATUS_PENDING,
                                'payment_type'     => 'card',
                                'subtotal'         => $calculation['subtotal'] ?? $payment->amount,
                                'tax'              => $calculation['tax'] ?? 0,
                                'discount'         => $calculation['discount'] ?? 0,
                                'total'            => $payment->amount,
                                'notes'            => $notes,
                            ]);

                            Log::info('📝 [ORDER] Order record created', [
                                'order_id'     => $order->id,
                                'order_number' => $order->order_number,
                            ]);

                            foreach ($orderItems as $item) {
                                Log::info(' [ORDER] Creating order item', [
                                    'menu_item_id' => $item['menu_item_id'] ?? 'missing',
                                    'quantity'     => $item['quantity'] ?? 'missing',
                                    'price'        => $item['price'] ?? 'missing',
                                ]);

                                $itemPrice = $item['price'] ?? 0;
                                $quantity = $item['quantity'] ?? 1;
                                $lineTotal = $itemPrice * $quantity;

                                $order->orderItems()->create([
                                    'menu_item_id'        => $item['menu_item_id'],
                                    'quantity'            => $quantity,
                                    'item_price_at_order' => $itemPrice,
                                    'line_total'          => $lineTotal,
                                    'notes'               => $item['special_instructions'] ?? null,
                                ]);
                            }

                            Log::info('📦 [ORDER] All order items created');

                            $payment->update(['order_id' => $order->id]);

                            Log::info(' [ORDER] Order created successfully after payment - NOW VISIBLE TO CHEF!', [
                                'payment_id'   => $payment->id,
                                'order_id'     => $order->id,
                                'order_number' => $order->order_number,
                                'status'       => $order->status,
                                'room_id'      => $roomId,
                                'total'        => $order->total,
                                'items_count'  => count($orderItems),
                            ]);

                        } catch (\Exception $e) {
                            Log::error(' [ORDER] Failed to create order after payment verification', [
                                'payment_id' => $payment->id,
                                'tx_ref'     => $txRef,
                                'error'      => $e->getMessage(),
                                'file'       => $e->getFile(),
                                'line'       => $e->getLine(),
                                'trace'      => $e->getTraceAsString(),
                            ]);
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'status'  => $payment->fresh()->status,
                    'payment' => new PaymentResource($payment->fresh()),
                ]);
            }

            Log::warning('Payment Status Not Successful', [
                'tx_ref'   => $txRef,
                'status'   => $response['data']['status'] ?? 'unknown',
                'payment'  => $payment->id,
            ]);

            $payment->markAsFailed($response);

            return response()->json([
                'success' => false,
                'message' => 'Payment was not completed successfully',
                'status'  => $payment->status,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Payment Not Found', ['tx_ref' => $txRef]);

            return response()->json([
                'success' => false,
                'message' => 'Payment record not found',
                'error'   => 'NOT_FOUND',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Payment Verify Exception', [
                'message' => $e->getMessage(),
                'tx_ref'  => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification',
                'error'   => 'EXCEPTION',
            ], 500);
        }
    }

    public function callback(Request $request): JsonResponse
    {
        try {
            Log::info('Chapa Callback Received', [
                'tx_ref' => $request->get('tx_ref'),
                'data'   => $request->all(),
            ]);

            $txRef = $request->get('tx_ref');

            if (!$txRef) {
                Log::warning('Chapa Callback Missing tx_ref');

                return response()->json([
                    'success' => false,
                    'message' => 'Missing transaction reference',
                ], 400);
            }

            return $this->verify($txRef);

        } catch (\Exception $e) {
            Log::error('Chapa Callback Exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred processing callback',
            ], 500);
        }
    }

    public function getStatus(string $paymentId): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            return response()->json([
                'success' => true,
                'payment' => new PaymentResource($payment),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Get Payment Status Exception', [
                'message'    => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    public function getByTransactionRef(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            return response()->json([
                'success' => true,
                'payment' => new PaymentResource($payment),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Get Payment By TxRef Exception', [
                'message' => $e->getMessage(),
                'tx_ref'  => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

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

            $per_page = $request->get('per_page', 15);
            $payments = $query->latest()->paginate($per_page);

            return response()->json([
                'success' => true,
                'data'    => PaymentResource::collection($payments),
                'meta'    => [
                    'total'        => $payments->total(),
                    'per_page'     => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('List Payments Exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred retrieving payments',
            ], 500);
        }
    }
}