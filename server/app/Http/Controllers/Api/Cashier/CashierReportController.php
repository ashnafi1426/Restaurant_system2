<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\TenantContext;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierReportController extends Controller
{
    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: TenantContext::id()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function revenueReport(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $period = $request->get('period', 'daily');
            $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateTimeString());
            $dateTo = $request->get('date_to', now()->endOfMonth()->toDateTimeString());

            $query = Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereBetween('paid_at', [$dateFrom, $dateTo]);

            // Consolidated metrics in 1 single aggregate query
            $summary = (clone $query)->selectRaw("
                SUM(amount) as total_revenue,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN reservation_id IS NOT NULL THEN amount ELSE 0 END) as reservation_revenue,
                SUM(CASE WHEN order_id IS NOT NULL THEN amount ELSE 0 END) as order_revenue
            ")->first();

            $totalRevenue = (float) ($summary->total_revenue ?? 0);
            $totalTransactions = (int) ($summary->total_transactions ?? 0);
            $reservationRevenue = (float) ($summary->reservation_revenue ?? 0);
            $orderRevenue = (float) ($summary->order_revenue ?? 0);
            $averageTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0.0;

            $revenueByMethod = (clone $query)
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->map(fn ($item) => [
                    'method' => $item->payment_method ?? 'Unknown',
                    'total' => (float) $item->total,
                ]);

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
        } catch (Exception $e) {
            Log::error('Cashier revenue report error', ['error' => $e->getMessage()]);

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
            $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateTimeString());
            $dateTo = $request->get('date_to', now()->endOfMonth()->toDateTimeString());

            $statusBreakdown = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->groupBy('status')
                ->get()
                ->map(fn ($item) => [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                    'total' => (float) $item->total,
                ]);

            $providerBreakdown = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereNotNull('payment_provider')
                ->select('payment_provider', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->groupBy('payment_provider')
                ->get()
                ->map(fn ($item) => [
                    'provider' => $item->payment_provider,
                    'count' => (int) $item->count,
                    'total' => (float) $item->total,
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'status_breakdown' => $statusBreakdown,
                    'provider_breakdown' => $providerBreakdown,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Cashier payment report error', ['error' => $e->getMessage()]);

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
            $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateTimeString());
            $dateTo = $request->get('date_to', now()->endOfMonth()->toDateTimeString());

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

            $refundsList = $refunds->map(fn ($payment) => [
                'id' => $payment->id,
                'tx_ref' => $payment->tx_ref,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'customer_name' => $payment->customer_name,
                'type' => $payment->reservation_id ? 'Reservation' : 'Restaurant Order',
                'refunded_at' => $payment->updated_at->format('Y-m-d H:i:s'),
            ]);

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
        } catch (Exception $e) {
            Log::error('Cashier refund report error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate refund report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getDailyBreakdown($dateFrom, $dateTo, ?string $hotelId = null): array
    {
        // Fetch all days in period in a single SQL query
        $dailyStats = Payment::whereBetween('paid_at', [$dateFrom, $dateTo])
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $days = [];
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        while ($start->lte($end)) {
            $date = $start->format('Y-m-d');
            $stat = $dailyStats->get($date);

            $days[] = [
                'date' => $date,
                'revenue' => (float) ($stat->revenue ?? 0),
                'transactions' => (int) ($stat->transactions ?? 0),
            ];

            $start->addDay();
        }

        return $days;
    }
}
