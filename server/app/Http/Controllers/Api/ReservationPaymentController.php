<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ChapaService;
use App\Services\PaymentService;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservationPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected ChapaService $chapaService
    ) {}

    /**
     * Initialize Chapa online payment for a hotel room booking.
     */
    public function initializePayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_id' => 'required|exists:rooms,id',
                'guest_id' => 'nullable|exists:guests,id',
                'check_in_date' => 'required|date|after_or_equal:today',
                'check_out_date' => 'required|date|after:check_in_date',
                'number_of_guests' => 'required|integer|min:1',
                'special_requests' => 'nullable|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'include_breakfast' => 'nullable|boolean',
                'include_dinner' => 'nullable|boolean',
                'include_spa' => 'nullable|boolean',
            ]);

            $validated['email'] = trim(strtolower($validated['email']));
            $validated['phone'] = $this->sanitizePhoneNumber($validated['phone']);

            $room = Room::with('roomType')->findOrFail($validated['room_id']);

            if ($room->status === 'maintenance' || !$room->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This room is currently unavailable or under maintenance.',
                ], 422);
            }

            $capacity = $room->roomType?->capacity ?? 2;
            if ((int) $validated['number_of_guests'] > $capacity) {
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
                $guest = Guest::withoutTenant()->findOrFail($validated['guest_id']);
            } else {
                $guest = Guest::withoutTenant()
                    ->where('hotel_id', $room->hotel_id)
                    ->where('email', $validated['email'])
                    ->first();

                if (!$guest) {
                    $guest = Guest::withoutTenant()->create([
                        'hotel_id' => $room->hotel_id,
                        'email' => $validated['email'],
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'phone' => $validated['phone'],
                    ]);
                }
            }
            $validated['guest_id'] = $guest->id;

            $priceBreakdown = $this->calculateReservationPrice(
                $room,
                $validated['check_in_date'],
                $validated['check_out_date'],
                $validated['include_breakfast'] ?? false,
                $validated['include_dinner'] ?? false,
                $validated['include_spa'] ?? false
            );

            $metadata = [
                'type' => 'reservation',
                'hotel_id' => $room->hotel_id,
                'room_id' => $validated['room_id'],
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'number_of_guests' => $validated['number_of_guests'],
                'special_requests' => $validated['special_requests'] ?? null,
                'include_breakfast' => $validated['include_breakfast'] ?? false,
                'include_dinner' => $validated['include_dinner'] ?? false,
                'include_spa' => $validated['include_spa'] ?? false,
                'price_breakdown' => $priceBreakdown,
            ];

            $payment = $this->paymentService->createReservationPayment([
                'hotel_id' => $room->hotel_id,
                'amount' => $priceBreakdown['total'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'guest_id' => $validated['guest_id'],
                'metadata' => $metadata,
            ]);

            $roomNumber = $room->room_number ?? 'N/A';
            $description = preg_replace(
                '/[^a-zA-Z0-9\-_\s\.]/',
                '',
                sprintf('%s - %s - Room %s', $metadata['check_in_date'], $metadata['check_out_date'], $roomNumber)
            );

            $returnUrl = config('chapa.return_url') . '?tx_ref=' . urlencode($payment->tx_ref);

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
                'title' => 'Hotel Booking',
                'description' => $description,
            ]);

            if (!($chapaResponse['success'] ?? false)) {
                $errorMessage = $chapaResponse['message'] ?? 'Unknown error';
                Log::error('Chapa Initialize Failed for Reservation', [
                    'payment_id' => $payment->id,
                    'error' => $errorMessage,
                    'response' => $chapaResponse,
                ]);

                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initialize payment: ' . $errorMessage,
                    'error' => $errorMessage,
                ], 400);
            }

            $checkoutUrl = $this->chapaService->getCheckoutUrl($chapaResponse);
            if (!$checkoutUrl) {
                Log::error('No checkout URL in Chapa response', ['payment_id' => $payment->id]);
                $payment->markAsFailed($chapaResponse);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway returned invalid response',
                ], 400);
            }

            $payment->markAsInitialized($checkoutUrl);

            Log::info('Reservation Payment Initialized', [
                'payment_id' => $payment->id,
                'room_id' => $validated['room_id'],
                'guest_id' => $validated['guest_id'],
                'amount' => $payment->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'payment_id' => $payment->id,
                'checkout_url' => $checkoutUrl,
                'tx_ref' => $payment->tx_ref,
                'amount' => $payment->amount,
                'price_breakdown' => $priceBreakdown,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Room or guest not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Reservation Payment Initialize Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred initializing payment',
            ], 500);
        }
    }

    /**
     * Finalize reservation booking after payment verification.
     */
    public function completeReservation(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)->firstOrFail();

            if (!$payment->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not been verified',
                ], 400);
            }

            // IDEMPOTENCY CHECK: return existing reservation if already completed
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
                $existingReservation->loadMissing(['guest', 'room.roomType']);

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation already completed',
                    'reservation' => $this->formatReservationData($existingReservation, $payment),
                    'payment' => new PaymentResource($payment->fresh()),
                ]);
            }

            $metadata = $payment->metadata;
            if (!$metadata || !isset($metadata['room_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment metadata',
                ], 400);
            }

            $result = $this->paymentService->handleReservationPaymentSuccess(
                $payment,
                [
                    'guest_id' => $payment->guest_id,
                    'room_id' => $metadata['room_id'],
                    'check_in_date' => $metadata['check_in_date'],
                    'check_out_date' => $metadata['check_out_date'],
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

            $reservation = $result['reservation'];
            $reservation->load(['guest', 'room.roomType']);

            Log::info('Reservation Created Successfully After Payment', [
                'payment_id' => $payment->id,
                'reservation_id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservation created successfully',
                'reservation' => $this->formatReservationData($reservation, $payment),
                'payment' => new PaymentResource($payment->fresh()),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Complete Reservation Exception', [
                'message' => $e->getMessage(),
                'tx_ref' => $txRef,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reservation and payment details by transaction reference.
     */
    public function getReservationByPayment(string $txRef): JsonResponse
    {
        try {
            $payment = Payment::where('tx_ref', $txRef)
                ->with(['reservation.guest', 'reservation.room.roomType'])
                ->firstOrFail();

            if (!$payment->reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reservation linked to this payment',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'reservation' => $this->formatReservationData($payment->reservation, $payment),
                'payment' => new PaymentResource($payment),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Get Reservation By Payment Exception', [
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
     * Calculate stay duration, subtotal, optional services, and tax.
     */
    private function calculateReservationPrice(
        Room $room,
        string $checkInDate,
        string $checkOutDate,
        bool $includeBreakfast = false,
        bool $includeDinner = false,
        bool $includeSpa = false
    ): array {
        $checkIn = new DateTime($checkInDate);
        $checkOut = new DateTime($checkOutDate);
        $numberOfNights = max(1, $checkOut->diff($checkIn)->days);

        $pricePerNight = (float) ($room->roomType?->base_price_per_night ?? $room->price ?? 0);
        $roomSubtotal = $pricePerNight * $numberOfNights;

        $breakfastPerNight = 0.0;
        $dinnerPerNight = 45.0;
        $spaPerNight = 35.0;

        $servicesTotal = 0.0;
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
            'price_per_night' => $pricePerNight,
            'number_of_nights' => $numberOfNights,
            'room_subtotal' => round($roomSubtotal, 2),
            'services' => $servicesBreakdown,
            'services_total' => round($servicesTotal, 2),
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Standardize reservation output payload.
     */
    private function formatReservationData(Reservation $reservation, Payment $payment): array
    {
        return [
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
    }

    /**
     * Format Ethiopian or international phone numbers.
     */
    private function sanitizePhoneNumber(string $phone): string
    {
        if (str_starts_with($phone, '+')) {
            return '+' . preg_replace('/[^0-9]/', '', substr($phone, 1));
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '0')) {
            return '+251' . substr($digits, 1);
        }

        if (str_starts_with($digits, '251')) {
            return '+' . $digits;
        }

        return '+251' . $digits;
    }
}
