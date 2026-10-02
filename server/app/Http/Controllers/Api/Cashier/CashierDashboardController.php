<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierDashboardController extends Controller
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

    public function index(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $baseQuery = Payment::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId));

            // Consolidated counts in a single query
            $counts = (clone $baseQuery)->selectRaw("
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as completed_payments,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as pending_payments,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as refund_requests
            ", [
                Payment::STATUS_PAID, Payment::STATUS_VERIFIED,
                Payment::STATUS_PENDING, Payment::STATUS_INITIALIZED,
                Payment::STATUS_FAILED,
                Payment::STATUS_REFUNDED,
            ])->first();

            $todayRevenue = (float) (clone $baseQuery)
                ->whereDate('paid_at', today())
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->sum('amount');

            $weeklyRevenue = (float) (clone $baseQuery)
                ->whereBetween('paid_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->sum('amount');

            $monthlyRevenue = (float) (clone $baseQuery)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->sum('amount');

            $stats = [
                'today_revenue' => $todayRevenue,
                'weekly_revenue' => $weeklyRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'pending_payments' => (int) ($counts->pending_payments ?? 0),
                'completed_payments' => (int) ($counts->completed_payments ?? 0),
                'failed_payments' => (int) ($counts->failed_payments ?? 0),
                'refund_requests' => (int) ($counts->refund_requests ?? 0),
                'total_transactions' => (int) ($counts->completed_payments ?? 0),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier dashboard index error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function recentPayments(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $payments = Payment::with(['guest', 'reservation', 'order'])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'tx_ref' => $payment->tx_ref,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'customer_name' => $payment->customer_name,
                    'email' => $payment->email,
                    'status' => $payment->status,
                    'payment_provider' => $payment->payment_provider,
                    'payment_method' => $payment->payment_method,
                    'type' => $payment->reservation_id ? 'Reservation' : 'Restaurant Order',
                    'reference' => $payment->reservation_id 
                        ? $payment->reservation?->id 
                        : $payment->order?->id,
                    'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier recent payments error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pendingPayments(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $payments = Payment::with(['guest', 'reservation', 'order'])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIALIZED])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'tx_ref' => $payment->tx_ref,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'customer_name' => $payment->customer_name,
                    'email' => $payment->email,
                    'status' => $payment->status,
                    'type' => $payment->reservation_id ? 'Reservation' : 'Restaurant Order',
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier pending payments error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function recentTransactions(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $transactions = Payment::with(['guest', 'reservation', 'order'])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->latest('paid_at')
                ->limit(10)
                ->get()
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'tx_ref' => $payment->tx_ref,
                    'chapa_transaction_id' => $payment->chapa_transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'customer_name' => $payment->customer_name,
                    'status' => $payment->status,
                    'payment_provider' => $payment->payment_provider,
                    'payment_method' => $payment->payment_method,
                    'type' => $payment->reservation_id ? 'Reservation' : 'Restaurant Order',
                    'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                    'verified_at' => $payment->verified_at?->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier recent transactions error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function revenueChart(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            // Single query grouped by date for past 7 days
            $revenues = Payment::whereBetween('paid_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayObj = now()->subDays($i);
                $date = $dayObj->format('Y-m-d');
                $last7Days[] = [
                    'date' => $date,
                    'label' => $dayObj->format('D'),
                    'revenue' => (float) ($revenues[$date] ?? 0),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $last7Days,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier revenue chart error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue chart data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentMethodChart(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $methods = Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->whereNotNull('payment_method')
                ->select('payment_method', DB::raw('count(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->map(fn ($item) => [
                    'method' => $item->payment_method ?? 'Unknown',
                    'count' => (int) $item->count,
                ]);

            return response()->json([
                'success' => true,
                'data' => $methods,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier payment method chart error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment method distribution',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refundRequests(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $refunds = Payment::with(['guest', 'reservation', 'order'])
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->where('status', Payment::STATUS_REFUNDED)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($payment) => [
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
                'data' => $refunds,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier refund requests error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch refund requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
