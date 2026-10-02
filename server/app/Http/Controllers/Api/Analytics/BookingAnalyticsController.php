<?php

namespace App\Http\Controllers\Api\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\TenantContext;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookingAnalyticsController extends Controller
{
    private function resolveHotelId(Request $request): ?string
    {
        return $request->input('hotel_id') ?? TenantContext::id() ?? auth()->user()?->hotel_id;
    }

    public function getOccupancyRate(Request $request): JsonResponse
    {
        try {
            $request->merge(['hotel_id' => $this->resolveHotelId($request)]);

            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];
            $fromDate = new DateTime($validated['from_date']);
            $toDate = new DateTime($validated['to_date']);

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
                        'daily_breakdown' => [],
                    ],
                ]);
            }

            $daysInPeriod = $toDate->diff($fromDate)->days + 1;
            $totalRoomNights = $totalRooms * $daysInPeriod;

            // Fetch reservations overlapping the range in a single query
            $reservations = Reservation::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->where('check_in_date', '<', $validated['to_date'])
                ->where('check_out_date', '>', $validated['from_date'])
                ->get(['check_in_date', 'check_out_date']);

            $occupiedNights = $reservations->sum(function ($reservation) use ($validated) {
                $resCheckIn = max(
                    new DateTime($validated['from_date']),
                    new DateTime($reservation->check_in_date)
                );
                $resCheckOut = min(
                    new DateTime($validated['to_date']),
                    new DateTime($reservation->check_out_date)
                );

                return max(0, $resCheckOut->diff($resCheckIn)->days);
            });

            $occupancyRate = $totalRoomNights > 0 ? ($occupiedNights / $totalRoomNights) * 100 : 0;

            // Calculate daily breakdown in-memory without N queries
            $dailyOccupancy = [];
            for ($i = 0; $i < $daysInPeriod; $i++) {
                $date = (clone $fromDate)->add(new DateInterval('P' . $i . 'D'));
                $dateStr = $date->format('Y-m-d');

                $roomsOccupied = $reservations->filter(function ($res) use ($dateStr) {
                    $cIn = Carbon::parse($res->check_in_date)->format('Y-m-d');
                    $cOut = Carbon::parse($res->check_out_date)->format('Y-m-d');
                    return $cIn <= $dateStr && $cOut > $dateStr;
                })->count();

                $dailyOccupancy[$dateStr] = [
                    'date' => $dateStr,
                    'rooms_occupied' => $roomsOccupied,
                    'occupancy_rate' => ($roomsOccupied / $totalRooms) * 100,
                ];
            }

            $averageDailyOccupancy = count($dailyOccupancy) > 0
                ? array_sum(array_column($dailyOccupancy, 'occupancy_rate')) / count($dailyOccupancy)
                : 0;

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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Occupancy rate error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating occupancy rate',
            ], 500);
        }
    }

    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $request->merge(['hotel_id' => $this->resolveHotelId($request)]);

            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];

            $payments = Payment::where('hotel_id', $hotelId)
                ->whereIn('status', ['verified', 'completed'])
                ->whereBetween('created_at', [
                    $validated['from_date'] . ' 00:00:00',
                    $validated['to_date'] . ' 23:59:59',
                ])
                ->get(['amount', 'payment_method', 'created_at']);

            $totalRevenue = (float) $payments->sum('amount');
            $paymentCount = $payments->count();
            $averagePayment = $paymentCount > 0 ? $totalRevenue / $paymentCount : 0.0;

            $revenueByMethod = $payments->groupBy('payment_method')->map(fn ($group) => [
                'method' => $group->first()?->payment_method ?? 'Unknown',
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])->values();

            $fromDate = new DateTime($validated['from_date']);
            $toDate = new DateTime($validated['to_date']);
            $daysInPeriod = $toDate->diff($fromDate)->days + 1;

            $dailyRevenue = [];
            for ($i = 0; $i < $daysInPeriod; $i++) {
                $date = (clone $fromDate)->add(new DateInterval('P' . $i . 'D'));
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

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel_id' => $hotelId,
                    'period' => [
                        'from' => $validated['from_date'],
                        'to' => $validated['to_date'],
                    ],
                    'total_revenue' => $totalRevenue,
                    'payment_count' => $paymentCount,
                    'average_payment' => (float) round($averagePayment, 2),
                    'currency' => 'ETB',
                    'revenue_by_method' => $revenueByMethod,
                    'daily_breakdown' => $dailyRevenue,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Revenue analytics error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating revenue analytics',
            ], 500);
        }
    }

    public function getBookingTrends(Request $request): JsonResponse
    {
        try {
            $request->merge(['hotel_id' => $this->resolveHotelId($request)]);

            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];
            $fromDateTime = $validated['from_date'] . ' 00:00:00';
            $toDateTime = $validated['to_date'] . ' 23:59:59';

            $statusDistribution = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as count')
                ->pluck('count', 'status');

            // Single query grouped by date for timeline instead of N queries
            $dailyCounts = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            $fromDate = new DateTime($validated['from_date']);
            $toDate = new DateTime($validated['to_date']);
            $daysInPeriod = $toDate->diff($fromDate)->days + 1;

            $bookingTimeline = [];
            for ($i = 0; $i < $daysInPeriod; $i++) {
                $date = (clone $fromDate)->add(new DateInterval('P' . $i . 'D'));
                $dateStr = $date->format('Y-m-d');

                $bookingTimeline[$dateStr] = [
                    'date' => $dateStr,
                    'bookings_created' => (int) ($dailyCounts[$dateStr] ?? 0),
                ];
            }

            $reservations = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->get(['check_in_date', 'check_out_date']);

            $totalDuration = $reservations->sum(fn ($r) => $r->total_nights);
            $averageDuration = count($reservations) > 0 ? $totalDuration / count($reservations) : 0;

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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Booking trends error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating booking trends',
            ], 500);
        }
    }

    public function getGuestStatistics(Request $request): JsonResponse
    {
        try {
            $request->merge(['hotel_id' => $this->resolveHotelId($request)]);

            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after:from_date',
            ]);

            $hotelId = $validated['hotel_id'];
            $fromDateTime = $validated['from_date'] . ' 00:00:00';
            $toDateTime = $validated['to_date'] . ' 23:59:59';

            $totalGuests = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->distinct('guest_id')
                ->count('guest_id');

            $guestBookingCounts = Reservation::where('hotel_id', $hotelId)
                ->whereIn('status', ['checked_in', 'checked_out', 'confirmed'])
                ->groupBy('guest_id')
                ->selectRaw('guest_id, COUNT(*) as booking_count')
                ->get();

            $repeatGuests = $guestBookingCounts->filter(fn ($g) => $g->booking_count > 1)->count();
            $newGuests = $guestBookingCounts->filter(fn ($g) => $g->booking_count === 1)->count();

            // Direct DB aggregate rather than loading all models into memory
            $averageGuestCount = Reservation::where('hotel_id', $hotelId)
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->avg('number_of_guests') ?? 0;

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
                    'average_guests_per_booking' => round((float) $averageGuestCount, 1),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Guest statistics error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating guest statistics',
            ], 500);
        }
    }

    public function getDashboardSummary(Request $request): JsonResponse
    {
        try {
            $request->merge(['hotel_id' => $this->resolveHotelId($request)]);

            $validated = $request->validate([
                'hotel_id' => 'required|exists:hotels,id',
            ]);

            $hotelId = $validated['hotel_id'];
            $today = now()->format('Y-m-d');
            $startOfMonth = now()->startOfMonth()->format('Y-m-d');
            $endOfMonth = now()->endOfMonth()->format('Y-m-d');

            $todayBookings = Reservation::where('hotel_id', $hotelId)
                ->whereDate('created_at', $today)
                ->count();

            $todayRevenue = Payment::where('hotel_id', $hotelId)
                ->whereIn('status', ['verified', 'completed'])
                ->whereDate('created_at', $today)
                ->sum('amount');

            $monthRevenue = Payment::where('hotel_id', $hotelId)
                ->whereIn('status', ['verified', 'completed'])
                ->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
                ->sum('amount');

            $activeReservations = Reservation::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->count();

            $totalRooms = Room::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->count();

            $occupiedRooms = Reservation::where('hotel_id', $hotelId)
                ->where('check_in_date', '<=', $today)
                ->where('check_out_date', '>', $today)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->count();

            $occupancyToday = $totalRooms > 0 ? ($occupiedRooms / $totalRooms) * 100 : 0;

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
        } catch (Exception $e) {
            Log::error('Dashboard summary error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred generating dashboard summary',
            ], 500);
        }
    }
}
