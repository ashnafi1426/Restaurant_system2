<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitializePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Reservation;
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
 * ReservationPaymentController
 * ============================================================================
 * Handles payment flow for hotel reservations
 * 
 * Payment Flow:
 * 1. Calculate reservation price based on room and dates
 * 2. Initialize payment with customer details
 * 3. Redirect customer to Chapa checkout
 * 4. Verify payment after customer returns
 * 5. Create reservation only after payment verification
 * 6. Return confirmation to customer
 * 
 * Reservation is NEVER created before successful payment verification
 * ============================================================================
 */
class ReservationPaymentController extends Controller
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
     * Initialize Reservation Payment
     * ============================================================================
     * Calculates total reservation cost and initializes payment
     * 
     * Request Body:
     * {
     *   "room_id": "uuid",
     *   "guest_id": "uuid",
     *   "check_in_date": "2026-08-15",
     *   "check_out_date": "2026-08-20",
     *   "number_of_guests": 2,
     *   "special_requests": "...",
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
            Log::info('Payment Initialize Called', [
                'request_data' => $request->all(),
            ]);

            // Validate request with less strict email validation for now
            $validated = $request->validate([
                'room_id'          => 'required|exists:rooms,id',
                'guest_id'         => 'required|exists:guests,id',
                'check_in_date'    => 'required|date|after_or_equal:today',
                'check_out_date'   => 'required|date|after:check_in_date',
                'number_of_guests' => 'required|integer|min:1',
                'special_requests' => 'nullable|string',
                'first_name'       => 'required|string|max:255',
                'last_name'        => 'required|string|max:255',
                'email'            => 'required|email',  // Basic email validation
                'phone'            => 'required|string|max:20',
                // Additional services
                'include_breakfast' => 'nullable|boolean',
                'include_dinner'    => 'nullable|boolean',
                'include_spa'       => 'nullable|boolean',
            ]);

            Log::info('Validation Passed');

            // Sanitize email - remove any whitespace, ensure lowercase
            $validated['email'] = trim(strtolower($validated['email']));
            
            // Validate email format more strictly
            if (!filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
                Log::error('Invalid email format', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format provided',
                ], 422);
            }
            
            // Sanitize phone - must be proper international format
            // Chapa requires format like +251912345678 or 0912345678
            $phone = $validated['phone'];
            
            // Remove all non-digit characters except leading +
            if (strpos($phone, '+') === 0) {
                // Keep the + if it's at the start
                $phone = '+' . preg_replace('/[^0-9]/', '', substr($phone, 1));
            } else {
                // Remove all non-digit characters
                $phone = preg_replace('/[^0-9]/', '', $phone);
                
                // Add country code if not present
                if (!str_starts_with($phone, '251') && !str_starts_with($phone, '0')) {
                    $phone = '+251' . $phone;
                } elseif (str_starts_with($phone, '0')) {
                    // Replace leading 0 with country code
                    $phone = '+251' . substr($phone, 1);
                } else {
                    $phone = '+' . $phone;
                }
            }
            
            // Validate phone length (Ethiopian phone numbers should be 12 digits with +251)
            $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($digitsOnly) < 9 || strlen($digitsOnly) > 12) {
                Log::error('Invalid phone number length', [
                    'phone_original' => $validated['phone'],
                    'phone_sanitized' => $phone,
                    'digits_count' => strlen($digitsOnly),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format. Please provide a valid Ethiopian phone number.',
                ], 422);
            }
            
            $validated['phone'] = $phone;
            
            Log::info('Input sanitized', [
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'phone_original' => $request->input('phone'),
            ]);

            // Get room details
            Log::info('Looking up room', ['room_id' => $validated['room_id']]);
            $room = Room::with('roomType')->findOrFail($validated['room_id']);
            Log::info('Room Found', [
                'room_id' => $room->id,
                'room_number' => $room->room_number ?? 'N/A',
                'has_room_number' => isset($room->room_number),
            ]);

            // Get guest details
            Log::info('Looking up guest', ['guest_id' => $validated['guest_id']]);
            $guest = Guest::findOrFail($validated['guest_id']);
            Log::info('Guest Found', ['guest_id' => $guest->id]);

            // Calculate reservation price
            Log::info('Calculating price');
            $priceBreakdown = $this->calculateReservationPrice(
                $room,
                $validated['check_in_date'],
                $validated['check_out_date'],
                $validated['include_breakfast'] ?? false,
                $validated['include_dinner'] ?? false,
                $validated['include_spa'] ?? false
            );
            Log::info('Price Calculated', ['breakdown' => $priceBreakdown]);
            
            // Prepare metadata
            $metadata = [
                'type'             => 'reservation',
                'room_id'          => $validated['room_id'],
                'check_in_date'    => $validated['check_in_date'],
                'check_out_date'   => $validated['check_out_date'],
                'number_of_guests' => $validated['number_of_guests'],
                'special_requests' => $validated['special_requests'] ?? null,
                'include_breakfast' => $validated['include_breakfast'] ?? false,
                'include_dinner'    => $validated['include_dinner'] ?? false,
                'include_spa'       => $validated['include_spa'] ?? false,
                'price_breakdown'  => $priceBreakdown,
            ];

            // Create payment record
            Log::info('Creating payment record');
            $payment = $this->paymentService->createReservationPayment([
                'amount'    => $priceBreakdown['total'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email'     => $validated['email'],
                'phone'     => $validated['phone'],
                'guest_id'  => $validated['guest_id'],
                'metadata'  => $metadata,
            ]);
            Log::info('Payment Record Created', ['payment_id' => $payment->id]);

            // Build description safely
            // Chapa allows only: letters, numbers, hyphens, underscores, spaces, and dots
            // Remove parentheses and other special characters
            $roomNumber = $room->room_number ?? 'N/A';
            $rawDescription = sprintf(
                '%s - %s - Room %s',
                $metadata['check_in_date'],
                $metadata['check_out_date'],
                $roomNumber
            );
            
            // Sanitize description - remove any characters not allowed by Chapa
            $description = preg_replace('/[^a-zA-Z0-9\-_\s\.]/', '', $rawDescription);
            Log::info('Payment Description', [
                'raw' => $rawDescription,
                'sanitized' => $description,
            ]);

            // Initialize payment with Chapa
            Log::info('Calling Chapa Initialize', [
                'amount' => $payment->amount,
                'email' => $payment->email,
                'tx_ref' => $payment->tx_ref,
            ]);
            
            // Build return URL with tx_ref parameter so Chapa passes it back
            $returnUrl = config('chapa.return_url') . '?tx_ref=' . urlencode($payment->tx_ref);
            
            $chapaResponse = $this->chapaService->initialize([
                'amount'       => $payment->amount,
                'currency'     => 'ETB',
                'email'        => $payment->email,
                'first_name'   => $payment->first_name,
                'last_name'    => $payment->last_name,
                'phone'        => $payment->phone,
                'tx_ref'       => $payment->tx_ref,
                'callback_url' => config('chapa.callback_url'),
                'return_url'   => $returnUrl,
                'title'        => 'Hotel Booking',
                'description'  => $description,
            ]);

            Log::info('Chapa Response Received', [
                'success' => $chapaResponse['success'] ?? false,
            ]);

            // Handle initialization failure
            if (!($chapaResponse['success'] ?? false)) {
                $errorMessage = $chapaResponse['message'] ?? 'Unknown error';
                $errorDetails = $chapaResponse['errors'] ?? $chapaResponse;
                
                Log::error('Chapa Initialize Failed for Reservation', [
                    'payment_id' => $payment->id,
                    'error'      => $errorMessage,
                    'error_details' => $errorDetails,
                    'full_response' => json_encode($chapaResponse),
                    'request_data' => [
                        'email' => $payment->email,
                        'phone' => $payment->phone,
                        'amount' => $payment->amount,
                        'tx_ref' => $payment->tx_ref,
                    ],
                ]);

                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment',
                    'error'   => $errorMessage,
                    'details' => is_array($errorDetails) && config('app.debug') ? $errorDetails : null,
                    'debug_info' => config('app.debug') ? $chapaResponse : null,
                ], 400);
            }

            // Update payment with checkout URL
            Log::info('Extracting checkout URL');
            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            
            Log::info('Checkout URL Extracted', [
                'has_url' => !empty($checkoutUrl),
            ]);
            
            if (!$checkoutUrl) {
                Log::error('No checkout URL in Chapa response', [
                    'payment_id' => $payment->id,
                    'response'   => $chapaResponse,
                ]);

                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway returned invalid response',
                ], 400);
            }
            
            $payment->markAsInitialized($checkoutUrl);

            Log::info('Reservation Payment Initialized', [
                'payment_id'  => $payment->id,
                'room_id'     => $validated['room_id'],
                'guest_id'    => $validated['guest_id'],
                'amount'      => $payment->amount,
            ]);

            // Return response
            return response()->json([
                'success'       => true,
                'message'       => 'Payment initialized successfully',
                'payment_id'    => $payment->id,
                'checkout_url'  => $checkoutUrl,
                'tx_ref'        => $payment->tx_ref,
                'amount'        => $payment->amount,
                'price_breakdown' => $priceBreakdown,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Exception', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Model Not Found Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Room or guest not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Reservation Payment Initialize Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $debug = config('app.debug');
            $errorInfo = $debug ? [
                'exception' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'class' => get_class($e),
            ] : null;

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'debug' => $errorInfo,
            ], 500);
        }
    }

    /**
     * ============================================================================
     * Complete Reservation After Payment
     * ============================================================================
     * Called after payment verification
     * Creates the actual reservation record
     * 
     * Only called by PaymentController after payment is verified
     * 
     * @param string $txRef - Transaction reference
     * @return JsonResponse
     */
    public function completeReservation(string $txRef): JsonResponse
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

            if (!$metadata || !isset($metadata['room_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment metadata',
                ], 400);
            }

            // Create reservation
            $result = $this->paymentService->handleReservationPaymentSuccess(
                $payment,
                [
                    'guest_id'         => $payment->guest_id,
                    'room_id'          => $metadata['room_id'],
                    'check_in_date'    => $metadata['check_in_date'],
                    'check_out_date'   => $metadata['check_out_date'],
                    'number_of_guests' => $metadata['number_of_guests'],
                    'special_requests' => $metadata['special_requests'] ?? null,
                ]
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            Log::info('Reservation Completed After Payment', [
                'payment_id'     => $payment->id,
                'reservation_id' => $result['reservation']->id,
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'Reservation created successfully',
                'reservation' => $result['reservation'],
                'payment'     => new PaymentResource($payment),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Complete Reservation Exception', [
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
     * Calculate Reservation Price
     * ============================================================================
     * Calculates total price for reservation based on room, dates, and services
     * 
     * @param Room $room
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param bool $includeBreakfast
     * @param bool $includeDinner
     * @param bool $includeSpa
     * 
     * @return array - Price breakdown
     */
    private function calculateReservationPrice(
        Room $room,
        string $checkInDate,
        string $checkOutDate,
        bool $includeBreakfast = false,
        bool $includeDinner = false,
        bool $includeSpa = false
    ): array {
        // Calculate number of nights
        $checkIn = new \DateTime($checkInDate);
        $checkOut = new \DateTime($checkOutDate);
        $numberOfNights = $checkOut->diff($checkIn)->days;

        if ($numberOfNights <= 0) {
            $numberOfNights = 1;
        }

        // Get room price (use room_type price if available)
        $pricePerNight = $room->roomType?->base_price_per_night ?? $room->price ?? 0;

        // Calculate room subtotal
        $roomSubtotal = $pricePerNight * $numberOfNights;

        // Calculate service charges (per night)
        $breakfastPerNight = 0;  // Breakfast is FREE
        $dinnerPerNight = 45;    // 45 ETB per night
        $spaPerNight = 35;       // 35 ETB per night

        $servicesTotal = 0;
        $servicesBreakdown = [];

        if ($includeBreakfast) {
            $breakfastTotal = $breakfastPerNight * $numberOfNights;
            $servicesTotal += $breakfastTotal;
            $servicesBreakdown['breakfast'] = [
                'included' => true,
                'price_per_night' => $breakfastPerNight,
                'total' => $breakfastTotal,
            ];
        }

        if ($includeDinner) {
            $dinnerTotal = $dinnerPerNight * $numberOfNights;
            $servicesTotal += $dinnerTotal;
            $servicesBreakdown['dinner'] = [
                'included' => true,
                'price_per_night' => $dinnerPerNight,
                'total' => $dinnerTotal,
            ];
        }

        if ($includeSpa) {
            $spaTotal = $spaPerNight * $numberOfNights;
            $servicesTotal += $spaTotal;
            $servicesBreakdown['spa'] = [
                'included' => true,
                'price_per_night' => $spaPerNight,
                'total' => $spaTotal,
            ];
        }

        // Calculate subtotal (room + services)
        $subtotal = $roomSubtotal + $servicesTotal;
        
        // Calculate tax (15% on subtotal)
        $tax = $subtotal * 0.15;
        
        // Calculate total
        $total = $subtotal + $tax;

        return [
            'price_per_night' => (float) $pricePerNight,
            'number_of_nights' => $numberOfNights,
            'room_subtotal'   => (float) $roomSubtotal,
            'services'        => $servicesBreakdown,
            'services_total'  => (float) $servicesTotal,
            'subtotal'        => (float) $subtotal,
            'tax'             => (float) $tax,
            'total'           => (float) $total,
        ];
    }

    /**
     * ============================================================================
     * Get Reservation by Payment
     * ============================================================================
     * Retrieve reservation linked to a payment
     * 
     * @param string $txRef - Transaction reference
     * @return JsonResponse
     */
    public function getReservationByPayment(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)
                ->with('reservation')
                ->firstOrFail();

            if (!$payment->reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reservation linked to this payment',
                ], 404);
            }

            return response()->json([
                'success'     => true,
                'reservation' => $payment->reservation,
                'payment'     => new PaymentResource($payment),
            ]);

        } catch (\Exception $e) {
            Log::error('Get Reservation By Payment Exception', [
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
