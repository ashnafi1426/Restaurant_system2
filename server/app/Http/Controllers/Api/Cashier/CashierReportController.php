<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierReportController extends Controller
{
    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: app(\App\Services\TenantContext::class)->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if ($hotelId) {
            app(\App\Services\TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function revenueReport(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $period = $request->get('period', 'daily');
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $query = Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereBetween('paid_at', [$dateFrom, $dateTo]);

            $totalRevenue = (float) (clone $query)->sum('amount');
            $totalTransactions = (clone $query)->count();
            $averageTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

            $reservationRevenue = (float) Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereNotNull('reservation_id')
                ->whereBetween('paid_at', [$dateFrom, $dateTo])
                ->sum('amount');

            $orderRevenue = (float) Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereNotNull('order_id')
                ->whereBetween('paid_at', [$dateFrom, $dateTo])
                ->sum('amount');

            $revenueByMethod = Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereBetween('paid_at', [$dateFrom, $dateTo])
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->payment_method ?? 'Unknown',
                        'total' => (float) $item->total,
                    ];
                });

            $dailyBreakdown = [];
            if ($period === 'daily') {
                $dailyBreakdown = $this->getDailyBreakdown($dateFrom, $dateTo, $hotelId);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'total_revenue' => $totalRevenue,
                    'total_transactions' => $totalTransactions,
                    'average_transaction' => round($averageTransaction, 2),
                    'reservation_revenue' => $reservationRevenue,
                    'order_revenue' => $orderRevenue,
                    'revenue_by_method' => $revenueByMethod,
                    'daily_breakdown' => $dailyBreakdown,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate revenue report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentReport(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $statusBreakdown = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'status' => $item->status,
                        'count' => (int) $item->count,
                        'total' => (float) $item->total,
                    ];
                });

            $providerBreakdown = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereNotNull('payment_provider')
                ->select('payment_provider', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->groupBy('payment_provider')
                ->get()
                ->map(function ($item) {
                    return [
                        'provider' => $item->payment_provider,
                        'count' => (int) $item->count,
                        'total' => (float) $item->total,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'status_breakdown' => $statusBreakdown,
                    'provider_breakdown' => $providerBreakdown,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refundReport(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $refunds = Payment::with(['guest', 'reservation', 'order'])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->where('status', Payment::STATUS_REFUNDED)
                ->whereBetween('updated_at', [$dateFrom, $dateTo])
                ->get();

            $totalRefunded = (float) $refunds->sum('amount');
            $totalCount = $refunds->count();

            $refundsByType = [
                'reservation' => (float) $refunds->whereNotNull('reservation_id')->sum('amount'),
                'order' => (float) $refunds->whereNotNull('order_id')->sum('amount'),
            ];

            $refundsList = $refunds->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'tx_ref' => $payment->tx_ref,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'customer_name' => $payment->customer_name,
                    'type' => $payment->reservation_id ? 'Reservation' : 'Restaurant Order',
                    'refunded_at' => $payment->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'total_refunded' => $totalRefunded,
                    'total_count' => $totalCount,
                    'refunds_by_type' => $refundsByType,
                    'refunds_list' => $refundsList,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate refund report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getDailyBreakdown($dateFrom, $dateTo, ?string $hotelId = null): array
    {
        $days = [];
        $start = \Carbon\Carbon::parse($dateFrom);
        $end = \Carbon\Carbon::parse($dateTo);

        while ($start->lte($end)) {
            $date = $start->format('Y-m-d');
            $revenue = (float) Payment::whereDate('paid_at', $date)
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->sum('amount');

            $count = Payment::whereDate('paid_at', $date)
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->count();

            $days[] = [
                'date' => $date,
                'revenue' => $revenue,
                'transactions' => $count,
            ];

            $start->addDay();
        }

        return $days;
    }
}
