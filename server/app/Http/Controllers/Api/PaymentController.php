<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitializePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Order;
use App\Services\ChapaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ============================================================================
 * PaymentController
 * ============================================================================
 * Handles Chapa payment processing for:
 * - Hotel Reservations
 * - Guest QR Food Orders
 * - Payment initialization, verification, and webhook handling
 * 
 * Payment Flow:
 * 1. Initialize payment (create Payment record in 'pending' status)
 * 2. Get checkout URL from Chapa
 * 3. Redirect customer to Chapa
 * 4. Customer completes payment
 * 5. Verify payment status
 * 6. Create associated records (Reservation/Order) only after verification
 * ============================================================================
 */
class PaymentController extends Controller
{
    /**
     * Chapa Service Instance
     */
    protected ChapaService $chapa;

    /**
     * Constructor - Inject ChapaService
     */
    public function __construct(ChapaService $chapa)
    {
        $this->chapa = $chapa;
    }

    /**
     * ============================================================================
     * Initialize Payment
     * ============================================================================
     * Validates payment request and initializes transaction with Chapa
     * 
     * Flow:
     * 1. Validate request data
     * 2. Generate unique transaction reference
     * 3. Create Payment record in 'pending' status
     * 4. Call Chapa API to initialize payment
     * 5. Update Payment with checkout URL and status='initialized'
     * 6. Return checkout URL to frontend
     * 
     * @param InitializePaymentRequest $request
     * @return JsonResponse
     */
    public function initialize(InitializePaymentRequest $request): JsonResponse
    {
        try {
            // Generate unique transaction reference
            $txRef = $this->chapa->generateTransactionReference();

            // Create Payment record (transaction starts at 'pending')
            $payment = Payment::create([
                'tx_ref'             => $txRef,
                'amount'             => $request->amount,
                'currency'           => 'ETB',
                'first_name'         => $request->first_name,
                'last_name'          => $request->last_name,
                'email'              => $request->email,
                'phone'              => $request->phone,
                'payment_provider'   => Payment::PROVIDER_CHAPA,
                'status'             => Payment::STATUS_PENDING,
                'metadata'           => $request->metadata ?? [],
            ]);

            // Initialize payment with Chapa
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

            // Handle initialization failure
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

            // Extract checkout URL
            $checkoutUrl = $this->chapa->getCheckoutUrl($response);

            // Update payment with checkout URL and initialized status
            $payment->markAsInitialized($checkoutUrl);

            Log::info('Payment Initialized Successfully', [
                'payment_id' => $payment->id,
                'tx_ref'     => $txRef,
                'amount'     => $payment->amount,
                'email'      => $payment->email,
            ]);

            // Return response with checkout URL
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

    /**
     * ============================================================================
     * Verify Payment
     * ============================================================================
     * Verifies payment status with Chapa and updates Payment record
     * 
     * Flow:
     * 1. Find Payment by transaction reference
     * 2. Query Chapa API for transaction status
     * 3. If verified/success, mark as verified and return success
     * 4. Create associated records (Reservation/Order) after verification
     * 5. Return verification result
     * 
     * @param string $txRef - Transaction Reference
     * @return JsonResponse
     */
    public function verify(string $txRef): JsonResponse
    {
        try {
            // Find payment record
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            // Query Chapa for verification
            $response = $this->chapa->verify($txRef);

            // Handle verification failure
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

            // Check if payment was successful
            if ($this->chapa->isSuccessful($response)) {
                // First mark as paid (sets paid_at timestamp and transaction ID)
                $payment->markAsPaid($this->chapa->getTransactionId($response));
                
                // Then mark as verified (sets verified_at and raw_response)
                $payment->markAsVerified($response);
                
                // Update payment method
                $payment->update([
                    'payment_method' => $this->chapa->getPaymentMethod($response),
                ]);

                Log::info('Payment Verified Successfully', [
                    'payment_id' => $payment->id,
                    'tx_ref'     => $txRef,
                    'amount'     => $payment->amount,
                    'status'     => $payment->fresh()->status,  // Get fresh status
                ]);

                // ============================================================================
                // AUTO-CREATE RESERVATION AFTER PAYMENT VERIFICATION
                // ============================================================================
                
                // Check if this is a reservation payment (has metadata with reservation data)
                if ($payment->metadata && isset($payment->metadata['type']) && $payment->metadata['type'] === 'reservation') {
                    Log::info('Creating reservation after payment verification', [
                        'payment_id' => $payment->id,
                        'tx_ref'     => $txRef,
                    ]);
                    
                    try {
                        // Create the reservation
                        $reservation = Reservation::create([
                            'booking_reference' => Reservation::generateBookingReference(),
                            'guest_id'          => $payment->guest_id,
                            'room_id'           => $payment->metadata['room_id'],
                            'check_in_date'     => $payment->metadata['check_in_date'],
                            'check_out_date'    => $payment->metadata['check_out_date'],
                            'number_of_guests'  => $payment->metadata['number_of_guests'],
                            'status'            => 'pending',  // Receptionist needs to confirm
                            'special_requests'  => $payment->metadata['special_requests'] ?? null,
                            'created_by'        => null,  // Guest booking
                        ]);
                        
                        // Link payment to reservation
                        $payment->update(['reservation_id' => $reservation->id]);
                        
                        Log::info('Reservation created successfully after payment', [
                            'payment_id'       => $payment->id,
                            'reservation_id'   => $reservation->id,
                            'booking_reference' => $reservation->booking_reference,
                            'status'           => $reservation->status,
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('Failed to create reservation after payment verification', [
                            'payment_id' => $payment->id,
                            'tx_ref'     => $txRef,
                            'error'      => $e->getMessage(),
                        ]);
                        // Don't fail the payment verification if reservation creation fails
                        // This can be handled manually or retried later
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'status'  => $payment->fresh()->status,
                    'payment' => new PaymentResource($payment->fresh()),
                ]);
            }

            // Payment not successful
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

    /**
     * ============================================================================
     * Chapa Callback
     * ============================================================================
     * Handles webhook callback from Chapa payment gateway
     * 
     * Flow:
     * 1. Extract transaction reference from callback payload
     * 2. Call verify method
     * 3. Create associated records (Reservation/Order) if verified
     * 4. Return response to Chapa
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function callback(Request $request): JsonResponse
    {
        try {
            Log::info('Chapa Callback Received', [
                'tx_ref' => $request->get('tx_ref'),
                'data'   => $request->all(),
            ]);

            // Extract transaction reference
            $txRef = $request->get('tx_ref');

            if (!$txRef) {
                Log::warning('Chapa Callback Missing tx_ref');

                return response()->json([
                    'success' => false,
                    'message' => 'Missing transaction reference',
                ], 400);
            }

            // Verify the payment
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

    /**
     * ============================================================================
     * Get Payment Status
     * ============================================================================
     * Retrieve current payment status
     * 
     * @param string $paymentId - Payment UUID
     * @return JsonResponse
     */
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

    /**
     * ============================================================================
     * Get Payment by Transaction Reference
     * ============================================================================
     * 
     * @param string $txRef
     * @return JsonResponse
     */
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

    /**
     * ============================================================================
     * List Payments (Admin/Manager)
     * ============================================================================
     * Retrieve paginated list of payments with filtering
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Payment::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by provider
            if ($request->has('provider')) {
                $query->where('payment_provider', $request->get('provider'));
            }

            // Filter by date range
            if ($request->has('from_date') && $request->has('to_date')) {
                $query->whereBetween('created_at', [
                    $request->get('from_date'),
                    $request->get('to_date'),
                ]);
            }

            // Filter by email
            if ($request->has('email')) {
                $query->where('email', 'like', '%' . $request->get('email') . '%');
            }

            // Pagination
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