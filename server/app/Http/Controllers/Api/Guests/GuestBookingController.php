<?php

namespace App\Http\Controllers\Api\Guests;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Hotel;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuestBookingController extends Controller
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Check room availability for given date range and guest count.
     * 
     * POST /guest/bookings/check-availability
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request)
    {
        try {
            Log::info('[GUEST BOOKING] Checking availability', [
                'ip' => $request->ip(),
                'hotel_id' => $this->tenantContext->getHotelId(),
            ]);

            // Validate input
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

            $checkInDate = $validated['check_in_date'];
            $checkOutDate = $validated['check_out_date'];
            $numGuests = $validated['num_guests'] ?? 1;
            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                Log::warning('[GUEST BOOKING] No hotel ID in tenant context');
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            // Query available rooms scoped to current hotel
            // Rooms with BelongsToTenant trait will auto-scope to hotel_id
            $availableRooms = Room::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->where('status', 'available')
                ->with('roomType')
                ->get()
                ->filter(function ($room) use ($checkInDate, $checkOutDate) {
                    // Check if room has any overlapping reservations
                    $hasConflict = Reservation::where('room_id', $room->id)
                        ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                        ->where(function ($query) use ($checkInDate, $checkOutDate) {
                            $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                                  ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                                  ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                                      $q->where('check_in_date', '<=', $checkInDate)
                                        ->where('check_out_date', '>=', $checkOutDate);
                                  });
                        })
                        ->exists();

                    return !$hasConflict;
                })
                ->values();

            $totalAvailable = $availableRooms->count();

            Log::info('[GUEST BOOKING] Availability check completed', [
                'hotel_id' => $hotelId,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'num_guests' => $numGuests,
                'available_rooms' => $totalAvailable,
            ]);

            return response()->json([
                'success' => true,
                'availableRooms' => $availableRooms->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'roomNumber' => $room->room_number,
                        'roomType' => $room->roomType ? $room->roomType->name : 'Standard',
                        'status' => $room->status,
                        'price' => $room->roomType ? (float) $room->roomType->price : 0,
                        'features' => $room->roomType ? ($room->roomType->features ?? []) : [],
                    ];
                })->toArray(),
                'totalAvailable' => $totalAvailable,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[GUEST BOOKING] Validation error on availability check', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[GUEST BOOKING] Error checking availability', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to check availability',
            ], 500);
        }
    }

    /**
     * Get room details by room ID.
     * 
     * GET /guest/bookings/rooms/{roomId}
     * 
     * @param string $roomId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoomDetails($roomId, Request $request)
    {
        try {
            Log::info('[GUEST BOOKING] Fetching room details', [
                'room_id' => $roomId,
                'hotel_id' => $this->tenantContext->getHotelId(),
            ]);

            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                Log::warning('[GUEST BOOKING] No hotel ID in tenant context for room details');
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            // Query room and verify it belongs to the current hotel
            $room = Room::where('id', $roomId)
                ->where('hotel_id', $hotelId)
                ->with('roomType')
                ->first();

            if (!$room) {
                Log::warning('[GUEST BOOKING] Room not found or access denied', [
                    'room_id' => $roomId,
                    'hotel_id' => $hotelId,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Room not found',
                ], 404);
            }

            if (!$room->is_active) {
                Log::warning('[GUEST BOOKING] Room is not active', [
                    'room_id' => $roomId,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Not available',
                    'message' => 'This room is currently not available',
                ], 403);
            }

            Log::info('[GUEST BOOKING] Room details retrieved', [
                'room_id' => $roomId,
                'room_number' => $room->room_number,
            ]);

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

        } catch (\Exception $e) {
            Log::error('[GUEST BOOKING] Error fetching room details', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to fetch room details',
            ], 500);
        }
    }

    /**
     * Create a new booking.
     * 
     * POST /guest/bookings
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createBooking(Request $request)
    {
        try {
            Log::info('[GUEST BOOKING] Creating new booking', [
                'hotel_id' => $this->tenantContext->getHotelId(),
            ]);

            // Validate input
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

            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                Log::warning('[GUEST BOOKING] No hotel ID in tenant context for booking creation');
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            // Verify room belongs to the current hotel and eagerly load roomType
            $room = Room::where('id', $validated['room_id'])
                ->where('hotel_id', $hotelId)
                ->with('roomType')
                ->first();

            if (!$room) {
                Log::warning('[GUEST BOOKING] Room not found or access denied', [
                    'room_id' => $validated['room_id'],
                    'hotel_id' => $hotelId,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Room not found or unavailable',
                ], 404);
            }

            // Verify room type belongs to the current hotel
            if ($room->roomType && $room->roomType->hotel_id !== $hotelId) {
                Log::warning('[GUEST BOOKING] RoomType hotel mismatch', [
                    'room_id' => $room->id,
                    'room_type_id' => $room->roomType->id,
                    'expected_hotel' => $hotelId,
                    'actual_hotel' => $room->roomType->hotel_id,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid configuration',
                    'message' => 'Room configuration error',
                ], 409);
            }

            // Check availability
            $hasConflict = Reservation::where('room_id', $room->id)
                ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('check_in_date', [$validated['check_in_date'], $validated['check_out_date']])
                          ->orWhereBetween('check_out_date', [$validated['check_in_date'], $validated['check_out_date']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('check_in_date', '<=', $validated['check_in_date'])
                                ->where('check_out_date', '>=', $validated['check_out_date']);
                          });
                })
                ->exists();

            if ($hasConflict) {
                Log::warning('[GUEST BOOKING] Room has conflicting reservation', [
                    'room_id' => $room->id,
                    'check_in_date' => $validated['check_in_date'],
                    'check_out_date' => $validated['check_out_date'],
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Conflict',
                    'message' => 'Room is not available for the requested dates',
                ], 409);
            }

            // Use transaction for consistency
            return DB::transaction(function () use ($validated, $room, $hotelId) {
                // Create or update guest
                $guest = Guest::where('hotel_id', $hotelId)
                    ->where('email', $validated['guest_info']['email'])
                    ->first();

                if (!$guest) {
                    $guest = Guest::create([
                        'hotel_id' => $hotelId,
                        'first_name' => $validated['guest_info']['first_name'],
                        'last_name' => $validated['guest_info']['last_name'],
                        'email' => $validated['guest_info']['email'],
                        'phone' => $validated['guest_info']['phone'],
                    ]);

                    Log::info('[GUEST BOOKING] New guest created', [
                        'guest_id' => $guest->id,
                        'email' => $guest->email,
                        'hotel_id' => $hotelId,
                    ]);
                } else {
                    Log::info('[GUEST BOOKING] Existing guest found', [
                        'guest_id' => $guest->id,
                        'email' => $guest->email,
                    ]);
                }

                // Calculate stay details
                $checkInDate = new \DateTime($validated['check_in_date']);
                $checkOutDate = new \DateTime($validated['check_out_date']);
                $stayDuration = $checkOutDate->diff($checkInDate)->days;
                $roomPrice = $room->roomType ? (float) $room->roomType->price : 0;
                $totalPrice = $stayDuration * $roomPrice;

                // Create reservation
                $reservation = Reservation::create([
                    'hotel_id' => $hotelId,
                    'booking_reference' => Reservation::generateBookingReference(),
                    'guest_id' => $guest->id,
                    'room_id' => $room->id,
                    'check_in_date' => $validated['check_in_date'],
                    'check_out_date' => $validated['check_out_date'],
                    'number_of_guests' => $validated['num_guests'] ?? 1,
                    'total_amount' => $totalPrice,
                    'status' => 'pending',
                    'special_requests' => $validated['special_requests'] ?? null,
                ]);

                Log::info('[GUEST BOOKING] Reservation created successfully', [
                    'reservation_id' => $reservation->id,
                    'booking_reference' => $reservation->booking_reference,
                    'guest_id' => $guest->id,
                    'room_id' => $room->id,
                    'total_amount' => $totalPrice,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'booking' => [
                        'reservationId' => $reservation->id,
                        'bookingReference' => $reservation->booking_reference,
                        'guestName' => $guest->full_name,
                        'roomNumber' => $room->room_number,
                        'checkInDate' => $reservation->check_in_date->format('Y-m-d'),
                        'checkOutDate' => $reservation->check_out_date->format('Y-m-d'),
                        'numberOfGuests' => $reservation->number_of_guests,
                        'stayDuration' => $stayDuration,
                        'total' => (float) $totalPrice,
                        'status' => $reservation->status,
                    ],
                ], 201);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[GUEST BOOKING] Validation error on booking creation', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[GUEST BOOKING] Error creating booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to create booking',
            ], 500);
        }
    }

    /**
     * Get booking status by booking reference.
     * 
     * GET /guest/bookings/{bookingReference}
     * 
     * @param string $bookingReference
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBookingStatus($bookingReference, Request $request)
    {
        try {
            Log::info('[GUEST BOOKING] Fetching booking status', [
                'booking_reference' => $bookingReference,
                'hotel_id' => $this->tenantContext->getHotelId(),
            ]);

            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                Log::warning('[GUEST BOOKING] No hotel ID in tenant context for status check');
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            // Query reservation scoped to current hotel
            $reservation = Reservation::where('hotel_id', $hotelId)
                ->where('booking_reference', $bookingReference)
                ->with(['guest', 'room'])
                ->first();

            if (!$reservation) {
                Log::warning('[GUEST BOOKING] Booking not found', [
                    'booking_reference' => $bookingReference,
                    'hotel_id' => $hotelId,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Booking not found',
                ], 404);
            }

            Log::info('[GUEST BOOKING] Booking status retrieved', [
                'booking_reference' => $bookingReference,
                'status' => $reservation->status,
            ]);

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

        } catch (\Exception $e) {
            Log::error('[GUEST BOOKING] Error fetching booking status', [
                'booking_reference' => $bookingReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to fetch booking status',
            ], 500);
        }
    }

    /**
     * Cancel a booking.
     * 
     * POST /guest/bookings/{bookingReference}/cancel
     * 
     * @param string $bookingReference
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelBooking($bookingReference, Request $request)
    {
        try {
            Log::info('[GUEST BOOKING] Cancelling booking', [
                'booking_reference' => $bookingReference,
                'hotel_id' => $this->tenantContext->getHotelId(),
            ]);

            // Validate input
            $validated = $request->validate([
                'cancellation_reason' => 'nullable|string|max:500',
            ]);

            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                Log::warning('[GUEST BOOKING] No hotel ID in tenant context for cancellation');
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Hotel context not found',
                ], 401);
            }

            // Query reservation scoped to current hotel
            $reservation = Reservation::where('hotel_id', $hotelId)
                ->where('booking_reference', $bookingReference)
                ->first();

            if (!$reservation) {
                Log::warning('[GUEST BOOKING] Booking not found for cancellation', [
                    'booking_reference' => $bookingReference,
                    'hotel_id' => $hotelId,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Booking not found',
                ], 404);
            }

            // Check if booking can be cancelled
            if (!$reservation->canCancel()) {
                Log::warning('[GUEST BOOKING] Booking cannot be cancelled', [
                    'booking_reference' => $bookingReference,
                    'status' => $reservation->status,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid status',
                    'message' => 'This booking cannot be cancelled in its current status',
                ], 422);
            }

            // Check 48-hour cancellation window
            $hoursUntilCheckIn = now()->diffInHours($reservation->check_in_date, false);
            if ($hoursUntilCheckIn < 48 && $hoursUntilCheckIn > 0) {
                Log::warning('[GUEST BOOKING] Cancellation within 48-hour window', [
                    'booking_reference' => $bookingReference,
                    'hours_until_checkin' => $hoursUntilCheckIn,
                ]);
                // Still allow cancellation but log the late cancellation
                Log::info('[GUEST BOOKING] Late cancellation allowed', [
                    'booking_reference' => $bookingReference,
                    'hours_until_checkin' => $hoursUntilCheckIn,
                ]);
            }

            // Update reservation status
            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Log cancellation reason
            Log::info('[GUEST BOOKING] Booking cancelled successfully', [
                'booking_reference' => $bookingReference,
                'cancellation_reason' => $validated['cancellation_reason'] ?? 'Not provided',
                'reservation_id' => $reservation->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'booking' => [
                    'bookingReference' => $reservation->booking_reference,
                    'status' => $reservation->status,
                    'cancelledAt' => $reservation->cancelled_at->toIso8601String(),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[GUEST BOOKING] Validation error on cancellation', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[GUEST BOOKING] Error cancelling booking', [
                'booking_reference' => $bookingReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to cancel booking',
            ], 500);
        }
    }
}
