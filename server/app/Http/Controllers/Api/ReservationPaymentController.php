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

    public function initializePayment(Request $request): JsonResponse
    {
        try {
            Log::info('Payment Initialize Called', [
                'request_data' => $request->all(),
            ]);

            $validated = $request->validate([
                'room_id'          => 'required|exists:rooms,id',
                'guest_id'         => 'nullable|exists:guests,id',
                'check_in_date'    => 'required|date|after_or_equal:today',
                'check_out_date'   => 'required|date|after:check_in_date',
                'number_of_guests' => 'required|integer|min:1',
                'special_requests' => 'nullable|string',
                'first_name'       => 'required|string|max:255',
                'last_name'        => 'required|string|max:255',
                'email'            => 'required|email',
                'phone'            => 'required|string|max:20',
                'include_breakfast' => 'nullable|boolean',
                'include_dinner'    => 'nullable|boolean',
                'include_spa'       => 'nullable|boolean',
            ]);

            Log::info('Validation Passed');

            $validated['email'] = trim(strtolower($validated['email']));
            
            if (!filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
                Log::error('Invalid email format', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format provided',
                ], 422);
            }
            
            $phone = $validated['phone'];
            
            if (strpos($phone, '+') === 0) {
                $phone = '+' . preg_replace('/[^0-9]/', '', substr($phone, 1));
            } else {
                $phone = preg_replace('/[^0-9]/', '', $phone);
                
                if (!str_starts_with($phone, '251') && !str_starts_with($phone, '0')) {
                    $phone = '+251' . $phone;
                } elseif (str_starts_with($phone, '0')) {
                    $phone = '+251' . substr($phone, 1);
                } else {
                    $phone = '+' . $phone;
                }
            }
            
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

            Log::info('Looking up room', ['room_id' => $validated['room_id']]);
            $room = Room::with('roomType')->findOrFail($validated['room_id']);
            Log::info('Room Found', [
                'room_id' => $room->id,
                'room_number' => $room->room_number ?? 'N/A',
                'has_room_number' => isset($room->room_number),
            ]);

            if ($room->status === 'maintenance' || !$room->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This room is currently unavailable or under maintenance.',
                ], 422);
            }

            $capacity = $room->roomType?->capacity ?? 2;
            if ((int)$validated['number_of_guests'] > $capacity) {
                return response()->json([
                    'success' => false,
                    'message' => "Number of guests exceeds room capacity (maximum {$capacity} guests).",
                ], 422);
            }

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

            $roomNumber = $room->room_number ?? 'N/A';
            $rawDescription = sprintf(
                '%s - %s - Room %s',
                $metadata['check_in_date'],
                $metadata['check_out_date'],
                $roomNumber
            );
            
            $description = preg_replace('/[^a-zA-Z0-9\-_\s\.]/', '', $rawDescription);
            Log::info('Payment Description', [
                'raw' => $rawDescription,
                'sanitized' => $description,
            ]);

            Log::info('Calling Chapa Initialize', [
                'amount' => $payment->amount,
                'email' => $payment->email,
                'tx_ref' => $payment->tx_ref,
            ]);
            
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

    public function completeReservation(string $txRef): JsonResponse
    {
        try {
            Log::info(' [COMPLETE] Starting reservation completion', ['tx_ref' => $txRef]);
            
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            Log::info(' [COMPLETE] Payment found', [
                'payment_id' => $payment->id,
                'is_verified' => $payment->isVerified(),
                'amount' => $payment->amount,
            ]);

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

            // IDEMPOTENCY CHECK: If a reservation is already associated with this payment, return it immediately
            $existingReservation = null;
            if (!empty($payment->reservation_id)) {
                $existingReservation = Reservation::withoutGlobalScopes()->find($payment->reservation_id);
            }

            if (!$existingReservation && !empty($payment->metadata['room_id'])) {
                $existingReservation = Reservation::withoutGlobalScopes()
                    ->where('guest_id', $payment->guest_id)
                    ->where('room_id', $payment->metadata['room_id'])
                    ->where('check_in_date', $payment->metadata['check_in_date'] ?? null)
                    ->where('check_out_date', $payment->metadata['check_out_date'] ?? null)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->first();
                if ($existingReservation) {
                    $payment->update(['reservation_id' => $existingReservation->id]);
                }
            }

            if ($existingReservation) {
                Log::info(' [COMPLETE] Reservation already exists for payment, returning existing record', [
                    'payment_id'     => $payment->id,
                    'reservation_id' => $existingReservation->id,
                    'booking_ref'    => $existingReservation->booking_reference,
                ]);
                $existingReservation->loadMissing(['guest', 'room.roomType']);

                $reservationData = [
                    'id' => $existingReservation->id,
                    'booking_reference' => $existingReservation->booking_reference,
                    'status' => $existingReservation->status,
                    'check_in_date' => $existingReservation->check_in_date ? $existingReservation->check_in_date->toDateString() : null,
                    'check_out_date' => $existingReservation->check_out_date ? $existingReservation->check_out_date->toDateString() : null,
                    'first_name' => $payment->first_name,
                    'last_name' => $payment->last_name,
                    'email' => $payment->email,
                    'phone' => $payment->phone,
                    'room_id' => $existingReservation->room_id,
                    'room_number' => $existingReservation->room ? $existingReservation->room->room_number : 'TBD',
                    'number_of_guests' => $existingReservation->number_of_guests,
                    'special_requests' => $existingReservation->special_requests,
                    'total_amount' => (float) $existingReservation->total_amount,
                    'currency' => 'ETB',
                    'created_at' => $existingReservation->created_at?->toIso8601String(),
                    'updated_at' => $existingReservation->updated_at?->toIso8601String(),
                ];

                return response()->json([
                    'success'     => true,
                    'message'     => 'Reservation already completed',
                    'reservation' => $reservationData,
                    'payment'     => new PaymentResource($payment->fresh()),
                ]);
            }

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

            Log::info(' [COMPLETE] Creating reservation with data', [
                'guest_id' => $payment->guest_id,
                'room_id' => $metadata['room_id'],
                'check_in' => $metadata['check_in_date'],
                'check_out' => $metadata['check_out_date'],
            ]);

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
            
            $reservation->load(['guest', 'room']);
            
            Log::info(' [COMPLETE] Reservation Created Successfully', [
                'payment_id'       => $payment->id,
                'reservation_id'   => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'total_amount'     => $reservation->total_amount,
            ]);

            $reservationData = [
                'id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'status' => $reservation->status,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->toDateString() : null,
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->toDateString() : null,
                'first_name' => $payment->first_name,
                'last_name' => $payment->last_name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                'room_id' => $reservation->room_id,
                'room_number' => $reservation->room ? $reservation->room->room_number : 'TBD',
                'number_of_guests' => $reservation->number_of_guests,
                'special_requests' => $reservation->special_requests,
                'total_amount' => (float) $reservation->total_amount,
                'currency' => 'ETB',
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

    private function calculateReservationPrice(
        Room $room,
        string $checkInDate,
        string $checkOutDate,
        bool $includeBreakfast = false,
        bool $includeDinner = false,
        bool $includeSpa = false
    ): array {
        $checkIn = new \DateTime($checkInDate);
        $checkOut = new \DateTime($checkOutDate);
        $numberOfNights = $checkOut->diff($checkIn)->days;

        if ($numberOfNights <= 0) {
            $numberOfNights = 1;
        }

        $pricePerNight = $room->roomType?->base_price_per_night ?? $room->price ?? 0;

        $roomSubtotal = $pricePerNight * $numberOfNights;

        $breakfastPerNight = 0;
        $dinnerPerNight = 45;
        $spaPerNight = 35;

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

        $subtotal = $roomSubtotal + $servicesTotal;
        $tax = $subtotal * 0.15;
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

            Log::info(' [RECEIPT API] Reservation data retrieved', [
                'reservation_id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'total_amount' => $reservation->total_amount,
                'has_guest' => !is_null($guest),
                'has_room' => !is_null($room),
            ]);

            $reservationData = [
                'id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'status' => $reservation->status,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->toDateString() : null,
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->toDateString() : null,
                'first_name' => $payment->first_name,
                'last_name' => $payment->last_name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                'room_id' => $reservation->room_id,
                'room_number' => $room ? $room->room_number : 'TBD',
                'number_of_guests' => $reservation->number_of_guests,
                'special_requests' => $reservation->special_requests,
                'total_amount' => (float) $reservation->total_amount,
                'currency' => 'ETB',
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
