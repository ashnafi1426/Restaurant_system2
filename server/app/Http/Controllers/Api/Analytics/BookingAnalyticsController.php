<?php

namespace App\Http\Controllers\Api\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * BookingAnalyticsController
 * ============================================================================
 * Provides analytics and reporting for booking system
 * 
 * Features:
 * - Occupancy rate calculation
 * - Revenue tracking by hotel
 * - Booking trends and patterns
 * - Guest statistics
 * - Room performance metrics
 * - Booking status distribution
 * ============================================================================
 */
class BookingAnalyticsController extends Controller
{
    /**
     * ============================================================================
     * Get Occupancy Rate
     * ============================================================================
     * Calculate occupancy rate for hotel over date range
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getOccupancyRate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];
            $fromDate = new \DateTime($validated['from_date']);
            $toDate = new \DateTime($validated['to_date']);

            // Total number of rooms in hotel
            $totalRooms = Room::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->count();

            if ($totalRooms === 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'hotel_id' => $hotelId,
                        'period' => [
                            'from' => $validated['from_date'],
                            'to' => $validated['to_date'],
                        ],
                        'total_rooms' => 0,
                        'total_room_nights' => 0,
                        'occupied_room_nights' => 0,
                        'occupancy_rate' => 0,
                        'average_occupancy_per_day' => 0,
                    ],
                ]);
            }

            // Calculate total room-nights (each room for each night in period)
            $daysInPeriod = $toDate->diff($fromDate)->days + 1;
            $totalRoomNights = $totalRooms * $daysInPeriod;

            // Get confirmed/checked-in reservations in period
            $occupiedNights = Reservation::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->where(function ($query) use ($validated) {
                    $query->where('check_in_date', '<', $validated['to_date'])
                        ->where('check_out_date', '>', $validated['from_date']);
                })
                ->get()
                ->sum(function ($reservation) use ($validated) {
                    // Calculate overlap between reservation and period
                    $resCheckIn = max(
                        new \DateTime($validated['from_date']),
                        $reservation->check_in_date
                    );
                    $resCheckOut = min(
                        new \DateTime($validated['to_date']),
                        $reservation->check_out_date
                    );

                    return $resCheckOut->diff($resCheckIn)->days;
                });

            $occupancyRate = $totalRoomNights > 0 ? ($occupiedNights / $totalRoomNights) * 100 : 0;

            // Calculate average occupancy per day
            $dailyOccupancy = [];
            for ($i = 0; $i < $daysInPeriod; $i++) {
                $date = (clone $fromDate)->add(new \DateInterval('P' . $i . 'D'));
                $dateStr = $date->format('Y-m-d');

                $roomsOccupied = Reservation::where('hotel_id', $hotelId)
                    ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                    ->where('check_in_date', '<=', $dateStr)
                    ->where('check_out_date', '>', $dateStr)
                    ->count();

                $dailyOccupancy[$dateStr] = [
                    'date' => $dateStr,
                    'rooms_occupied' => $roomsOccupied,
                    'occupancy_rate' => ($roomsOccupied / $totalRooms) * 100,
                ];
            }

            $averageDailyOccupancy = count($dailyOccupancy) > 0
                ? array_sum(array_column($dailyOccupancy, 'occupancy_rate')) / count($dailyOccupancy)
                : 0;

            Log::info('📊 [ANALYTICS] Occupancy rate calculated', [
                'hotel_id' => $hotelId,
                'occupancy_rate' => $occupancyRate,
                'period_days' => $daysInPeriod,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel_id' => $hotelId,
                    'period' => [
                        'from' => $validated['from_date'],
                        'to' => $validated['to_date'],
                    ],
                    'total_rooms' => $totalRooms,
                    'total_room_nights' => $totalRoomNights,
                    'occupied_room_nights' => (int) $occupiedNights,
                    'occupancy_rate' => round($occupancyRate, 2),
                    'average_occupancy_per_day' => round($averageDailyOccupancy, 2),
                    'daily_breakdown' => $dailyOccupancy,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [ANALYTICS] Occupancy rate exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating occupancy rate',
            ], 500);
        }
    }

    /**
     * ============================================================================
     * Get Revenue Analytics
     * ============================================================================
     * Calculate and retrieve revenue metrics by hotel and date range
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];

            // Get completed/verified payments
            $payments = Payment::where('hotel_id', $hotelId)
                ->whereIn('status', ['verified', 'completed'])
                ->whereBetween('created_at', [
                    $validated['from_date'] . ' 00:00:00',
                    $validated['to_date'] . ' 23:59:59',
                ])
                ->get();

            $totalRevenue = $payments->sum('amount');
            $paymentCount = $payments->count();
            $averagePayment = $paymentCount > 0 ? $totalRevenue / $paymentCount : 0;

            // Revenue by payment method
            $revenueByMethod = $payments->groupBy('payment_method')->map(fn ($group) => [
                'method' => $group->first()?->payment_method ?? 'Unknown',
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])->values();

            // Daily revenue breakdown
            $dailyRevenue = [];
            $fromDate = new \DateTime($validated['from_date']);
            $toDate = new \DateTime($validated['to_date']);
            $daysInPeriod = $toDate->diff($fromDate)->days + 1;

            for ($i = 0; $i < $daysInPeriod; $i++) {
                $date = (clone $fromDate)->add(new \DateInterval('P' . $i . 'D'));
                $dateStr = $date->format('Y-m-d');

                $dayPayments = $payments->filter(function ($payment) use ($dateStr) {
                    return $payment->created_at->format('Y-m-d') === $dateStr;
                });

                $dailyRevenue[$dateStr] = [
                    'date' => $dateStr,
                    'amount' => (float) $dayPayments->sum('amount'),
                    'count' => $dayPayments->count(),
                ];
            }

            Log::info('💰 [ANALYTICS] Revenue analytics calculated', [
                'hotel_id' => $hotelId,
                'total_revenue' => $totalRevenue,
                'payment_count' => $paymentCount,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel_id' => $hotelId,
                    'period' => [
                        'from' => $validated['from_date'],
                        'to' => $validated['to_date'],
                    ],
                    'total_revenue' => (float) $totalRevenue,
                    'payment_count' => $paymentCount,
                    'average_payment' => (float) $averagePayment,
                    'currency' => 'ETB',
                    'revenue_by_method' => $revenueByMethod,
                    'daily_breakdown' => $dailyRevenue,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [ANALYTICS] Revenue analytics exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating revenue analytics',
            ], 500);
        }
    }

    /**
     * ============================================================================
     * Get Booking Trends
     * ============================================================================
     * Analyze booking trends and patterns
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBookingTrends(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];

            // Get booking status distribution
            $statusDistribution = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [
                    $validated['from_date'] . ' 00:00:00',
                    $validated['to_date'] . ' 23:59:59',
                ])
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as count')
                ->pluck('count', 'status');

            // Booking timeline
            $bookingTimeline = [];
            $fromDate = new \DateTime($validated['from_date']);
            $toDate = new \DateTime($validated['to_date']);
            $daysInPeriod = $toDate->diff($fromDate)->days + 1;

            for ($i = 0; $i < $daysInPeriod; $i++) {
                $date = (clone $fromDate)->add(new \DateInterval('P' . $i . 'D'));
                $dateStr = $date->format('Y-m-d');

                $count = Reservation::where('hotel_id', $hotelId)
                    ->whereDate('created_at', $dateStr)
                    ->count();

                $bookingTimeline[$dateStr] = [
                    'date' => $dateStr,
                    'bookings_created' => $count,
                ];
            }

            // Average booking duration
            $reservations = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [
                    $validated['from_date'] . ' 00:00:00',
                    $validated['to_date'] . ' 23:59:59',
                ])
                ->get();

            $totalDuration = $reservations->sum(fn ($r) => $r->total_nights);
            $averageDuration = count($reservations) > 0 ? $totalDuration / count($reservations) : 0;

            Log::info('📈 [ANALYTICS] Booking trends calculated', [
                'hotel_id' => $hotelId,
                'total_bookings' => count($reservations),
                'average_duration' => $averageDuration,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel_id' => $hotelId,
                    'period' => [
                        'from' => $validated['from_date'],
                        'to' => $validated['to_date'],
                    ],
                    'total_bookings' => count($reservations),
                    'status_distribution' => $statusDistribution->toArray(),
                    'average_booking_duration' => round($averageDuration, 1),
                    'booking_timeline' => $bookingTimeline,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [ANALYTICS] Booking trends exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating booking trends',
            ], 500);
        }
    }

    /**
     * ============================================================================
     * Get Guest Statistics
     * ============================================================================
     * Retrieve guest-related statistics and metrics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getGuestStatistics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];

            // Total unique guests
            $totalGuests = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [
                    $validated['from_date'] . ' 00:00:00',
                    $validated['to_date'] . ' 23:59:59',
                ])
                ->distinct('guest_id')
                ->count();

            // Repeat guests
            $guestBookingCounts = Reservation::where('hotel_id', $hotelId)
                ->whereIn('status', ['checked_in', 'checked_out', 'confirmed'])
                ->groupBy('guest_id')
                ->selectRaw('guest_id, COUNT(*) as booking_count')
                ->get();

            $repeatGuests = $guestBookingCounts->filter(fn ($g) => $g->booking_count > 1)->count();
            $newGuests = $guestBookingCounts->filter(fn ($g) => $g->booking_count === 1)->count();

            // Average guests per booking
            $totalGuests_ = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [
                    $validated['from_date'] . ' 00:00:00',
                    $validated['to_date'] . ' 23:59:59',
                ])
                ->get();

            $averageGuestCount = count($totalGuests_) > 0
                ? $totalGuests_->sum('number_of_guests') / count($totalGuests_)
                : 0;

            Log::info('👥 [ANALYTICS] Guest statistics calculated', [
                'hotel_id' => $hotelId,
                'total_unique_guests' => $totalGuests,
                'repeat_guests' => $repeatGuests,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel_id' => $hotelId,
                    'period' => [
                        'from' => $validated['from_date'],
                        'to' => $validated['to_date'],
                    ],
                    'total_unique_guests' => $totalGuests,
                    'repeat_guests' => $repeatGuests,
                    'new_guests' => $newGuests,
                    'average_guests_per_booking' => round($averageGuestCount, 1),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [ANALYTICS] Guest statistics exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating guest statistics',
            ], 500);
        }
    }

    /**
     * ============================================================================
     * Get Dashboard Summary
     * ============================================================================
     * Get comprehensive dashboard summary for quick overview
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
            ]);

            $hotelId = $validated['hotel_id'];
            $today = now()->format('Y-m-d');
            $startOfMonth = now()->startOfMonth()->format('Y-m-d');
            $endOfMonth = now()->endOfMonth()->format('Y-m-d');

            // Today's bookings
            $todayBookings = Reservation::where('hotel_id', $hotelId)
                ->whereDate('created_at', $today)
                ->count();

            // Today's revenue
            $todayRevenue = Payment::where('hotel_id', $hotelId)
                ->whereIn('status', ['verified', 'completed'])
                ->whereDate('created_at', $today)
                ->sum('amount');

            // Month's revenue
            $monthRevenue = Payment::where('hotel_id', $hotelId)
                ->whereIn('status', ['verified', 'completed'])
                ->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
                ->sum('amount');

            // Active reservations
            $activeReservations = Reservation::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->count();

            // Occupancy today
            $totalRooms = Room::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->count();

            $occupiedRooms = Reservation::where('hotel_id', $hotelId)
                ->where('check_in_date', '<=', $today)
                ->where('check_out_date', '>', $today)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->count();

            $occupancyToday = $totalRooms > 0 ? ($occupiedRooms / $totalRooms) * 100 : 0;

            Log::info('📊 [ANALYTICS] Dashboard summary generated', [
                'hotel_id' => $hotelId,
                'today_bookings' => $todayBookings,
                'today_revenue' => $todayRevenue,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel_id' => $hotelId,
                    'today' => [
                        'date' => $today,
                        'bookings_created' => $todayBookings,
                        'revenue' => (float) $todayRevenue,
                        'occupied_rooms' => $occupiedRooms,
                        'total_rooms' => $totalRooms,
                        'occupancy_rate' => round($occupancyToday, 2),
                    ],
                    'month' => [
                        'revenue' => (float) $monthRevenue,
                        'start_date' => $startOfMonth,
                        'end_date' => $endOfMonth,
                    ],
                    'active_reservations' => $activeReservations,
                    'currency' => 'ETB',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [ANALYTICS] Dashboard summary exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred generating dashboard summary',
            ], 500);
        }
    }
}
