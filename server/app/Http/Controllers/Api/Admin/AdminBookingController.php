<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminBookingController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    /**
     * Display a listing of bookings for the current hotel.
     */
    public function index(Request $request): JsonResponse
    {
        $hotelId = TenantContext::id();
        if (!$hotelId) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to identify hotel context.',
            ], 403);
        }

        $perPage = $request->integer('per_page', 15);
        $reservations = $this->reservationService->getReservations($request->all(), $perPage, $hotelId);

        return response()->json([
            'success' => true,
            'data' => ReservationResource::collection($reservations),
            'pagination' => [
                'total' => $reservations->total(),
                'per_page' => $reservations->perPage(),
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'from' => $reservations->firstItem(),
                'to' => $reservations->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified booking.
     */
    public function show(string $reservationId): JsonResponse
    {
        $hotelId = TenantContext::id();
        $reservation = Reservation::where('hotel_id', $hotelId)
            ->where('id', $reservationId)
            ->with(['guest', 'room.roomType', 'creator', 'checkIn'])
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'The requested reservation does not exist or you do not have access.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
        ]);
    }

    /**
     * Update the specified booking.
     */
    public function update(Request $request, string $reservationId): JsonResponse
    {
        $hotelId = TenantContext::id();
        $reservation = Reservation::where('hotel_id', $hotelId)->where('id', $reservationId)->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'The requested reservation does not exist.',
            ], 404);
        }

        $validated = $request->validate([
            'check_in_date' => 'sometimes|date',
            'check_out_date' => 'sometimes|date|after:check_in_date',
            'number_of_guests' => 'sometimes|integer|min:1',
            'room_id' => 'sometimes|uuid|exists:rooms,id',
            'status' => 'sometimes|in:pending,confirmed,checked_in,checked_out,cancelled',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        try {
            $updated = $this->reservationService->updateReservation($reservation, $validated, $hotelId);

            return response()->json([
                'success' => true,
                'message' => 'Reservation updated successfully',
                'data' => new ReservationResource($updated),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Cancel the specified booking.
     */
    public function destroy(Request $request, string $reservationId): JsonResponse
    {
        $hotelId = TenantContext::id();
        $reservation = Reservation::where('hotel_id', $hotelId)->where('id', $reservationId)->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'The requested reservation does not exist.',
            ], 404);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $cancelled = $this->reservationService->cancelReservation($reservation, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully',
            'data' => new ReservationResource($cancelled),
        ]);
    }
}
