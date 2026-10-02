<?php

namespace App\Http\Controllers\Api\Guests;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GuestBookingController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected ReservationService $reservationService
    ) {}

    protected function getHotelId(): ?string
    {
        return $this->tenantContext->getHotelId() ?? TenantContext::id() ?? auth()->user()?->hotel_id;
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'check_in_date' => 'required|date_format:Y-m-d|after_or_equal:today',
                'check_out_date' => 'required|date_format:Y-m-d|after:check_in_date',
                'num_guests' => 'nullable|integer|min:1|max:10',
            ], [
                'check_in_date.required' => 'Check-in date is required',
                'check_in_date.date_format' => 'Check-in date must be in Y-m-d format',
                'check_in_date.after_or_equal' => 'Check-in date must be today or later',
                'check_out_date.required' => 'Check-out date is required',
                'check_out_date.date_format' => 'Check-out date must be in Y-m-d format',
                'check_out_date.after' => 'Check-out date must be after check-in date',
                'num_guests.integer' => 'Number of guests must be an integer',
                'num_guests.min' => 'Minimum 1 guest required',
                'num_guests.max' => 'Maximum 10 guests allowed',
            ]);

            $hotelId = $this->getHotelId();
            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            $res = $this->reservationService->checkAvailability([
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'capacity' => $validated['num_guests'] ?? 1,
            ], $hotelId);

            $availableRooms = $res['rooms'];

            return response()->json([
                'success' => true,
                'availableRooms' => $availableRooms->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'roomNumber' => $room->room_number,
                        'roomType' => $room->roomType ? $room->roomType->name : 'Standard',
                        'status' => $room->status,
                        'price' => $room->roomType ? (float) ($room->roomType->base_price_per_night ?? $room->roomType->price ?? 0) : 0,
                        'features' => $room->roomType ? ($room->roomType->features ?? []) : [],
                    ];
                })->toArray(),
                'totalAvailable' => $availableRooms->count(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('[GUEST BOOKING] Error checking availability', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to check availability',
            ], 500);
        }
    }

    public function getRoomDetails(string $roomId, Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            $room = Room::where('id', $roomId)
                ->where('hotel_id', $hotelId)
                ->with('roomType')
                ->first();

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Room not found',
                ], 404);
            }

            if (!$room->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not available',
                    'message' => 'This room is currently not available',
                ], 403);
            }

            $roomType = $room->roomType;

            return response()->json([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'roomNumber' => $room->room_number,
                    'roomType' => $roomType ? $roomType->name : 'Standard',
                    'floor' => $room->floor,
                    'features' => $roomType ? ($roomType->features ?? []) : [],
                    'price' => $roomType ? (float) $roomType->price : 0,
                    'description' => $room->description,
                    'status' => $room->status,
                    'images' => $roomType ? ($roomType->images ?? []) : [],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('[GUEST BOOKING] Error fetching room details', ['room_id' => $roomId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to fetch room details',
            ], 500);
        }
    }

    public function createBooking(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'guest_info' => 'required|array',
                'guest_info.first_name' => 'required|string|min:2|max:100',
                'guest_info.last_name' => 'required|string|min:2|max:100',
                'guest_info.email' => 'required|email|max:100',
                'guest_info.phone' => 'required|string|regex:/^[\d\+\-\s\(\)]+$/',
                'room_id' => 'required|uuid|exists:rooms,id',
                'check_in_date' => 'required|date_format:Y-m-d|after_or_equal:today',
                'check_out_date' => 'required|date_format:Y-m-d|after:check_in_date',
                'num_guests' => 'nullable|integer|min:1|max:10',
                'special_requests' => 'nullable|string|max:500',
            ], [
                'guest_info.required' => 'Guest information is required',
                'guest_info.first_name.required' => 'Guest first name is required',
                'guest_info.last_name.required' => 'Guest last name is required',
                'guest_info.email.required' => 'Guest email is required',
                'guest_info.email.email' => 'Guest email must be a valid email address',
                'guest_info.phone.required' => 'Guest phone is required',
                'guest_info.phone.regex' => 'Guest phone format is invalid',
                'room_id.required' => 'Room ID is required',
                'room_id.uuid' => 'Room ID must be a valid UUID',
                'room_id.exists' => 'Room not found',
                'check_in_date.required' => 'Check-in date is required',
                'check_in_date.after_or_equal' => 'Check-in date must be today or later',
                'check_out_date.required' => 'Check-out date is required',
                'check_out_date.after' => 'Check-out date must be after check-in date',
            ]);

            $hotelId = $this->getHotelId();
            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            $reservation = $this->reservationService->createReservation($validated, $hotelId);
            $stayDuration = max(1, $reservation->check_out_date->diffInDays($reservation->check_in_date));

            return response()->json([
                'success' => true,
                'message' => 'Booking created and confirmed successfully',
                'booking' => [
                    'reservationId' => $reservation->id,
                    'bookingReference' => $reservation->booking_reference,
                    'guestName' => $reservation->guest ? $reservation->guest->full_name : 'Guest',
                    'roomNumber' => $reservation->room ? $reservation->room->room_number : '',
                    'checkInDate' => $reservation->check_in_date->format('Y-m-d'),
                    'checkOutDate' => $reservation->check_out_date->format('Y-m-d'),
                    'numberOfGuests' => $reservation->number_of_guests,
                    'stayDuration' => $stayDuration,
                    'total' => (float) $reservation->total_amount,
                    'status' => $reservation->status,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('[GUEST BOOKING] Error creating booking', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to create booking',
            ], 500);
        }
    }

    public function getBookingStatus(string $bookingReference, Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            $reservation = Reservation::where('hotel_id', $hotelId)
                ->where('booking_reference', $bookingReference)
                ->with(['guest', 'room'])
                ->first();

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Booking not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'booking' => [
                    'bookingReference' => $reservation->booking_reference,
                    'status' => $reservation->status,
                    'checkInDate' => $reservation->check_in_date->format('Y-m-d'),
                    'checkOutDate' => $reservation->check_out_date->format('Y-m-d'),
                    'guestName' => $reservation->guest ? $reservation->guest->full_name : 'Guest',
                    'guestEmail' => $reservation->guest ? $reservation->guest->email : '',
                    'guestPhone' => $reservation->guest ? $reservation->guest->phone : '',
                    'roomNumber' => $reservation->room ? $reservation->room->room_number : '',
                    'numberOfGuests' => $reservation->number_of_guests,
                    'total' => (float) $reservation->total_amount,
                    'specialRequests' => $reservation->special_requests,
                    'createdAt' => $reservation->created_at->toIso8601String(),
                    'updatedAt' => $reservation->updated_at->toIso8601String(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('[GUEST BOOKING] Error fetching booking status', [
                'booking_reference' => $bookingReference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to fetch booking status',
            ], 500);
        }
    }

    public function cancelBooking(string $bookingReference, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cancellation_reason' => 'nullable|string|max:500',
            ]);

            $hotelId = $this->getHotelId();
            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            $reservation = Reservation::where('hotel_id', $hotelId)
                ->where('booking_reference', $bookingReference)
                ->first();

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Booking not found',
                ], 404);
            }

            if (!$reservation->canCancel()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid status',
                    'message' => 'This booking cannot be cancelled in its current status',
                ], 422);
            }

            $cancelled = $this->reservationService->cancelReservation($reservation, $validated['cancellation_reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'booking' => [
                    'bookingReference' => $cancelled->booking_reference,
                    'status' => $cancelled->status,
                    'cancelledAt' => $cancelled->cancelled_at?->toIso8601String(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('[GUEST BOOKING] Error cancelling booking', [
                'booking_reference' => $bookingReference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to cancel booking',
            ], 500);
        }
    }
}
