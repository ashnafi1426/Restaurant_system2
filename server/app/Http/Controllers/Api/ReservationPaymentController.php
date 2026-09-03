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
                'guest_id'         => 'nullable|exists:guests,id',
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

            // Enforce room active and not in maintenance
            if ($room->status === 'maintenance' || !$room->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This room is currently unavailable or under maintenance.',
                ], 422);
            }

            // Enforce room capacity
            $capacity = $room->roomType?->capacity ?? 2;
            if ((int)$validated['number_of_guests'] > $capacity) {
                return response()->json([
                    'success' => false,
                    'message' => "Number of guests exceeds room capacity (maximum {$capacity} guests).",
                ], 422);
            }

            // Enforce date overlap availability strictly within this hotel
            $hasConflict = Reservation::where('hotel_id', $room->hotel_id)
                ->where('room_id', $room->id)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where(function ($query) use ($validated) {
                    $query->where('check_in_date', '<', $validated['check_out_date'])
                          ->where('check_out_date', '>', $validated['check_in_date']);
                })
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected room is already booked for these dates.',
                ], 422);
            }

            // Get or create guest details
            if (!empty($validated['guest_id'])) {
                Log::info('Looking up guest', ['guest_id' => $validated['guest_id']]);
                $guest = Guest::withoutTenant()->findOrFail($validated['guest_id']);
            } else {
                Log::info('Finding or creating guest', ['email' => $validated['email'], 'hotel_id' => $room->hotel_id]);
                $guest = Guest::withoutTenant()
                    ->where('hotel_id', $room->hotel_id)
                    ->where('email', $validated['email'])
                    ->first();

                if (!$guest) {
                    $guest = Guest::withoutTenant()->create([
                        'hotel_id'   => $room->hotel_id,
                        'email'      => $validated['email'],
                        'first_name' => $validated['first_name'],
                        'last_name'  => $validated['last_name'],
                        'phone'      => $validated['phone'],
                    ]);
                }
            }
            $validated['guest_id'] = $guest->id;
            Log::info('Guest Found or Created', ['guest_id' => $guest->id]);

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
            
            // Prepare metadata with hotel_id
            $metadata = [
                'type'             => 'reservation',
                'hotel_id'         => $room->hotel_id,
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

            // Create payment record with hotel_id
            Log::info('Creating payment record');
            $payment = $this->paymentService->createReservationPayment([
                'hotel_id'   => $room->hotel_id,
                'amount'     => $priceBreakdown['total'],
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'phone'      => $validated['phone'],
                'guest_id'   => $validated['guest_id'],
                'metadata'   => $metadata,
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
                    'message' => 'Unable to initialize payment: ' . $errorMessage,
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
            Log::info('🔄 [COMPLETE] Starting reservation completion', ['tx_ref' => $txRef]);
            
            // Find payment
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            Log::info(' [COMPLETE] Payment found', [
                'payment_id' => $payment->id,
                'is_verified' => $payment->isVerified(),
                'amount' => $payment->amount,
            ]);

            // Verify payment is verified
            if (!$payment->isVerified()) {
                Log::warning(' [COMPLETE] Payment not verified', [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not been verified',
                ], 400);
            }

            // Get metadata
            $metadata = $payment->metadata;

            if (!$metadata || !isset($metadata['room_id'])) {
                Log::error(' [COMPLETE] Invalid metadata', [
                    'payment_id' => $payment->id,
                    'has_metadata' => !is_null($metadata),
                    'has_room_id' => isset($metadata['room_id']),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment metadata',
                ], 400);
            }

            Log::info('📋 [COMPLETE] Creating reservation with data', [
                'guest_id' => $payment->guest_id,
                'room_id' => $metadata['room_id'],
                'check_in' => $metadata['check_in_date'],
                'check_out' => $metadata['check_out_date'],
            ]);

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
                Log::error(' [COMPLETE] Reservation creation failed', [
                    'message' => $result['message'],
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            $reservation = $result['reservation'];
            
            // Load relationships
            $reservation->load(['guest', 'room']);
            
            Log::info(' [COMPLETE] Reservation Created Successfully', [
                'payment_id'       => $payment->id,
                'reservation_id'   => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'total_amount'     => $reservation->total_amount,
            ]);

            // Build comprehensive reservation data for frontend
            $reservationData = [
                // Booking info
                'id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'status' => $reservation->status,
                
                // Dates
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->toDateString() : null,
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->toDateString() : null,
                
                // Guest info (for receipt) - Use payment data as source of truth
                'first_name' => $payment->first_name,
                'last_name' => $payment->last_name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                
                // Room info
                'room_id' => $reservation->room_id,
                'room_number' => $reservation->room ? $reservation->room->room_number : 'TBD',
                
                // Booking details
                'number_of_guests' => $reservation->number_of_guests,
                'special_requests' => $reservation->special_requests,
                
                // Payment info - ⭐ CRITICAL for receipt
                'total_amount' => (float) $reservation->total_amount, // ← THIS FIXES THE 0 ETB ISSUE!
                'currency' => 'ETB',
                
                // Timestamps
                'created_at' => $reservation->created_at?->toIso8601String(),
                'updated_at' => $reservation->updated_at?->toIso8601String(),
            ];

            Log::info('📤 [COMPLETE] Response prepared with all fields', [
                'booking_reference' => $reservationData['booking_reference'],
                'total_amount' => $reservationData['total_amount'],
                'has_guest_info' => isset($reservationData['first_name'], $reservationData['email']),
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'Reservation created successfully',
                'reservation' => $reservationData,
                'payment'     => new PaymentResource($payment->fresh()),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error(' [COMPLETE] Payment not found', ['tx_ref' => $txRef]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error(' [COMPLETE] Complete Reservation Exception', [
                'message' => $e->getMessage(),
                'tx_ref'  => $txRef,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
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
            Log::info('📡 [RECEIPT API] Getting reservation by payment', ['tx_ref' => $txRef]);
            
            $payment = Payment::where('tx_ref', $txRef)
                ->with(['reservation.guest', 'reservation.room'])
                ->firstOrFail();

            Log::info(' [RECEIPT API] Payment found', [
                'payment_id' => $payment->id,
                'has_reservation' => !is_null($payment->reservation),
                'amount' => $payment->amount,
            ]);

            if (!$payment->reservation) {
                Log::warning(' [RECEIPT API] No reservation linked to payment', [
                    'payment_id' => $payment->id,
                    'tx_ref' => $txRef,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No reservation linked to this payment',
                ], 404);
            }

            $reservation = $payment->reservation;
            $guest = $reservation->guest;
            $room = $reservation->room;

            Log::info('📋 [RECEIPT API] Reservation data retrieved', [
                'reservation_id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'total_amount' => $reservation->total_amount,
                'has_guest' => !is_null($guest),
                'has_room' => !is_null($room),
            ]);

            // Build comprehensive reservation data for frontend
            $reservationData = [
                // Booking info
                'id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'status' => $reservation->status,
                
                // Dates
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->toDateString() : null,
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->toDateString() : null,
                
                // Guest info (for receipt)
                'first_name' => $payment->first_name, // Use payment data (always has it)
                'last_name' => $payment->last_name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                
                // Room info
                'room_id' => $reservation->room_id,
                'room_number' => $room ? $room->room_number : 'TBD',
                
                // Booking details
                'number_of_guests' => $reservation->number_of_guests,
                'special_requests' => $reservation->special_requests,
                
                // Payment info - ⭐ CRITICAL for receipt
                'total_amount' => (float) $reservation->total_amount, // ← THIS FIXES THE 0 ETB ISSUE!
                'currency' => 'ETB',
                
                // Timestamps
                'created_at' => $reservation->created_at?->toIso8601String(),
                'updated_at' => $reservation->updated_at?->toIso8601String(),
            ];

            Log::info(' [RECEIPT API] Response prepared', [
                'booking_reference' => $reservationData['booking_reference'],
                'total_amount' => $reservationData['total_amount'],
                'has_all_fields' => isset($reservationData['first_name'], $reservationData['email'], $reservationData['total_amount']),
            ]);

            return response()->json([
                'success'     => true,
                'reservation' => $reservationData,
                'payment'     => new PaymentResource($payment),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error(' [RECEIPT API] Payment not found', ['tx_ref' => $txRef]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error(' [RECEIPT API] Get Reservation By Payment Exception', [
                'message' => $e->getMessage(),
                'tx_ref'  => $txRef,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }
}
