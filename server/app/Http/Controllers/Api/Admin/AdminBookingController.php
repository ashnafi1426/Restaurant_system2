<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Guest;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminBookingController extends Controller
{
    protected TenantContext $tenantContext;

    /**
     * Instantiate the controller with middleware
     */
    public function __construct(TenantContext $tenantContext)
    {
        // Require authentication via 'auth:sanctum'
        // Auto-scoping to authenticated user's hotel via TenantContext
        $this->middleware('auth:sanctum');
        $this->tenantContext = $tenantContext;
    }

    /**
     * List reservations with filtering and pagination
     * GET /admin/bookings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hotel context not found',
                    'message' => 'Unable to identify hotel context.',
                ], 403);
            }

            Log::info('[ADMIN BOOKING] Listing reservations', [
                'user_id' => $user->id,
                'hotel_id' => $hotelId,
                'filters' => $request->all(),
            ]);

            // Start query with automatic tenant scoping via BelongsToTenant trait
            $query = Reservation::where('hotel_id', $hotelId)
                ->with([
                    'guest',
                    'room.roomType',
                    'creator',
                    'checkIn',
                ]);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', strtolower(trim($request->status)));
            }

            if ($request->filled('guest_name')) {
                $searchName = trim($request->guest_name);
                $query->whereHas('guest', function ($q) use ($searchName) {
                    $q->where('first_name', 'LIKE', "%{$searchName}%")
                      ->orWhere('last_name', 'LIKE', "%{$searchName}%");
                });
            }

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            if ($request->filled('check_in_date')) {
                $query->whereDate('check_in_date', '>=', $request->check_in_date);
            }

            if ($request->filled('check_out_date')) {
                $query->whereDate('check_out_date', '<=', $request->check_out_date);
            }

            // Paginate results
            $perPage = $request->integer('per_page', 15);
            $reservations = $query->latest()->paginate($perPage);

            Log::info('[ADMIN BOOKING] Reservations fetched successfully', [
                'user_id' => $user->id,
                'count' => $reservations->count(),
                'total' => $reservations->total(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $reservations->map(function ($reservation) {
                    return $this->formatReservation($reservation);
                }),
                'pagination' => [
                    'total' => $reservations->total(),
                    'per_page' => $reservations->perPage(),
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'from' => $reservations->firstItem(),
                    'to' => $reservations->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ADMIN BOOKING] Error listing reservations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch reservations',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get full reservation details
     * GET /admin/bookings/{reservationId}
     *
     * @param string $reservationId
     * @return JsonResponse
     */
    public function show(string $reservationId): JsonResponse
    {
        try {
            $user = Auth::user();
            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hotel context not found',
                    'message' => 'Unable to identify hotel context.',
                ], 403);
            }

            Log::info('[ADMIN BOOKING] Fetching reservation details', [
                'user_id' => $user->id,
                'reservation_id' => $reservationId,
                'hotel_id' => $hotelId,
            ]);

            // Fetch reservation with all relationships
            $reservation = Reservation::where('hotel_id', $hotelId)
                ->where('id', $reservationId)
                ->with([
                    'guest',
                    'room.roomType.hotel',
                    'creator',
                    'checkIn',
                ])
                ->first();

            if (!$reservation) {
                Log::warning('[ADMIN BOOKING] Reservation not found or access denied', [
                    'user_id' => $user->id,
                    'reservation_id' => $reservationId,
                    'hotel_id' => $hotelId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Reservation not found',
                    'message' => 'The requested reservation does not exist or you do not have access.',
                ], 404);
            }

            Log::info('[ADMIN BOOKING] Reservation details retrieved successfully', [
                'user_id' => $user->id,
                'reservation_id' => $reservationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatReservationDetail($reservation),
            ]);
        } catch (\Exception $e) {
            Log::error('[ADMIN BOOKING] Error fetching reservation details', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch reservation details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update reservation details
     * PUT /admin/bookings/{reservationId}
     *
     * @param Request $request
     * @param string $reservationId
     * @return JsonResponse
     */
    public function update(Request $request, string $reservationId): JsonResponse
    {
        try {
            $user = Auth::user();
            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hotel context not found',
                    'message' => 'Unable to identify hotel context.',
                ], 403);
            }

            Log::info('[ADMIN BOOKING] Updating reservation', [
                'user_id' => $user->id,
                'reservation_id' => $reservationId,
                'hotel_id' => $hotelId,
            ]);

            // Validate input
            $validated = $request->validate([
                'check_in_date' => 'nullable|date|after_or_equal:today',
                'check_out_date' => 'nullable|date|after:check_in_date',
                'room_id' => 'nullable|exists:rooms,id',
                'special_requests' => 'nullable|string|max:500',
            ]);

            // Find reservation with tenant scoping
            $reservation = Reservation::where('hotel_id', $hotelId)
                ->where('id', $reservationId)
                ->first();

            if (!$reservation) {
                Log::warning('[ADMIN BOOKING] Reservation not found for update', [
                    'user_id' => $user->id,
                    'reservation_id' => $reservationId,
                    'hotel_id' => $hotelId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Reservation not found',
                    'message' => 'The requested reservation does not exist or you do not have access.',
                ], 404);
            }

            // Check if room belongs to the same hotel
            if ($request->filled('room_id')) {
                $room = Room::where('hotel_id', $hotelId)
                    ->where('id', $request->room_id)
                    ->first();

                if (!$room) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid room',
                        'message' => 'The selected room does not exist or belongs to a different hotel.',
                    ], 422);
                }

                // Check room availability for new dates
                if ($request->filled('check_in_date') || $request->filled('check_out_date')) {
                    $checkInDate = $request->check_in_date ?? $reservation->check_in_date;
                    $checkOutDate = $request->check_out_date ?? $reservation->check_out_date;

                    $conflict = Reservation::where('hotel_id', $hotelId)
                        ->where('room_id', $request->room_id)
                        ->where('id', '!=', $reservationId)
                        ->where(function ($q) use ($checkInDate, $checkOutDate) {
                            $q->where(function ($sq) use ($checkInDate, $checkOutDate) {
                                $sq->whereDate('check_in_date', '<', $checkOutDate)
                                   ->whereDate('check_out_date', '>', $checkInDate);
                            });
                        })
                        ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                        ->exists();

                    if ($conflict) {
                        Log::warning('[ADMIN BOOKING] Room not available for dates', [
                            'room_id' => $request->room_id,
                            'check_in_date' => $checkInDate,
                            'check_out_date' => $checkOutDate,
                        ]);

                        return response()->json([
                            'success' => false,
                            'error' => 'Room not available',
                            'message' => 'The selected room is not available for the specified dates.',
                        ], 422);
                    }
                }
            }

            // Update reservation
            DB::beginTransaction();
            try {
                $oldData = $reservation->toArray();

                $reservation->update([
                    'check_in_date' => $validated['check_in_date'] ?? $reservation->check_in_date,
                    'check_out_date' => $validated['check_out_date'] ?? $reservation->check_out_date,
                    'room_id' => $validated['room_id'] ?? $reservation->room_id,
                    'special_requests' => $validated['special_requests'] ?? $reservation->special_requests,
                ]);

                // Log the modification
                Log::info('[ADMIN BOOKING] Reservation updated successfully', [
                    'user_id' => $user->id,
                    'reservation_id' => $reservationId,
                    'hotel_id' => $hotelId,
                    'changes' => [
                        'old' => $oldData,
                        'new' => $reservation->toArray(),
                    ],
                    'timestamp' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation updated successfully',
                    'data' => $this->formatReservationDetail($reservation->fresh([
                        'guest',
                        'room.roomType',
                        'creator',
                        'checkIn',
                    ])),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            Log::warning('[ADMIN BOOKING] Validation failed for update', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[ADMIN BOOKING] Error updating reservation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update reservation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel/delete reservation
     * DELETE /admin/bookings/{reservationId}
     *
     * @param Request $request
     * @param string $reservationId
     * @return JsonResponse
     */
    public function destroy(Request $request, string $reservationId): JsonResponse
    {
        try {
            $user = Auth::user();
            $hotelId = $this->tenantContext->getHotelId();

            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hotel context not found',
                    'message' => 'Unable to identify hotel context.',
                ], 403);
            }

            Log::info('[ADMIN BOOKING] Deleting reservation', [
                'user_id' => $user->id,
                'reservation_id' => $reservationId,
                'hotel_id' => $hotelId,
            ]);

            // Validate input
            $validated = $request->validate([
                'reason' => 'nullable|string|max:255',
            ]);

            // Find reservation with tenant scoping
            $reservation = Reservation::where('hotel_id', $hotelId)
                ->where('id', $reservationId)
                ->first();

            if (!$reservation) {
                Log::warning('[ADMIN BOOKING] Reservation not found for deletion', [
                    'user_id' => $user->id,
                    'reservation_id' => $reservationId,
                    'hotel_id' => $hotelId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Reservation not found',
                    'message' => 'The requested reservation does not exist or you do not have access.',
                ], 404);
            }

            // Store deletion data for audit
            $deletionData = [
                'reservation_id' => $reservation->id,
                'booking_reference' => $reservation->booking_reference,
                'guest_id' => $reservation->guest_id,
                'room_id' => $reservation->room_id,
                'check_in_date' => $reservation->check_in_date,
                'check_out_date' => $reservation->check_out_date,
                'status' => $reservation->status,
                'total_amount' => $reservation->total_amount,
                'deleted_by' => $user->id,
                'deletion_reason' => $validated['reason'] ?? null,
                'deletion_timestamp' => now(),
            ];

            // Soft delete or change status to 'cancelled'
            DB::beginTransaction();
            try {
                // Update status to cancelled
                $reservation->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                // Log deletion with audit information
                Log::info('[ADMIN BOOKING] Reservation deleted successfully', [
                    'user_id' => $user->id,
                    'reservation_id' => $reservationId,
                    'hotel_id' => $hotelId,
                    'deletion_data' => $deletionData,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation cancelled successfully',
                    'data' => [
                        'reservation_id' => $reservation->id,
                        'booking_reference' => $reservation->booking_reference,
                        'status' => $reservation->status,
                        'cancelled_at' => $reservation->cancelled_at,
                    ],
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            Log::warning('[ADMIN BOOKING] Validation failed for deletion', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[ADMIN BOOKING] Error deleting reservation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel reservation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format reservation for list response
     *
     * @param Reservation $reservation
     * @return array
     */
    private function formatReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'booking_reference' => $reservation->booking_reference,
            'guest' => [
                'id' => $reservation->guest->id ?? null,
                'name' => $reservation->guest ? trim($reservation->guest->first_name . ' ' . $reservation->guest->last_name) : 'N/A',
                'email' => $reservation->guest->email ?? null,
                'phone' => $reservation->guest->phone ?? null,
            ],
            'room' => [
                'id' => $reservation->room->id ?? null,
                'room_number' => $reservation->room->room_number ?? null,
                'room_type' => $reservation->room->roomType->name ?? 'N/A',
            ],
            'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
            'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
            'number_of_guests' => $reservation->number_of_guests,
            'total_nights' => $reservation->total_nights,
            'total_amount' => $reservation->total_amount ? (float)$reservation->total_amount : null,
            'status' => $reservation->status,
            'special_requests' => $reservation->special_requests,
            'created_at' => $reservation->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $reservation->updated_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $reservation->cancelled_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format reservation for detail response
     *
     * @param Reservation $reservation
     * @return array
     */
    private function formatReservationDetail(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'booking_reference' => $reservation->booking_reference,
            'guest' => [
                'id' => $reservation->guest->id ?? null,
                'first_name' => $reservation->guest->first_name ?? null,
                'last_name' => $reservation->guest->last_name ?? null,
                'email' => $reservation->guest->email ?? null,
                'phone' => $reservation->guest->phone ?? null,
                'country' => $reservation->guest->country ?? null,
            ],
            'room' => [
                'id' => $reservation->room->id ?? null,
                'room_number' => $reservation->room->room_number ?? null,
                'room_type' => [
                    'id' => $reservation->room->roomType->id ?? null,
                    'name' => $reservation->room->roomType->name ?? 'N/A',
                    'price' => (float)($reservation->room->roomType->price ?? 0),
                ],
                'status' => $reservation->room->status ?? 'unknown',
            ],
            'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
            'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
            'number_of_guests' => $reservation->number_of_guests,
            'total_nights' => $reservation->total_nights,
            'total_amount' => $reservation->total_amount ? (float)$reservation->total_amount : null,
            'status' => $reservation->status,
            'special_requests' => $reservation->special_requests,
            'check_in_history' => $reservation->checkIn ? [
                'id' => $reservation->checkIn->id,
                'checked_in_at' => $reservation->checkIn->checked_in_at?->format('Y-m-d H:i:s'),
                'checked_out_at' => $reservation->checkIn->checked_out_at?->format('Y-m-d H:i:s'),
            ] : null,
            'created_by' => [
                'id' => $reservation->creator->id ?? null,
                'name' => $reservation->creator ? ($reservation->creator->first_name . ' ' . $reservation->creator->last_name) : 'System',
                'email' => $reservation->creator->email ?? null,
            ],
            'created_at' => $reservation->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $reservation->updated_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $reservation->cancelled_at?->format('Y-m-d H:i:s'),
        ];
    }
}
