<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CashierDashboardController extends Controller
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

    public function index(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $stats = [
                'today_revenue' => $this->getTodayRevenue($hotelId),
                'weekly_revenue' => $this->getWeeklyRevenue($hotelId),
                'monthly_revenue' => $this->getMonthlyRevenue($hotelId),
                'pending_payments' => $this->getPendingPaymentsCount($hotelId),
                'completed_payments' => $this->getCompletedPaymentsCount($hotelId),
                'failed_payments' => $this->getFailedPaymentsCount($hotelId),
                'refund_requests' => $this->getRefundRequestsCount($hotelId),
                'total_transactions' => $this->getTotalTransactionsCount($hotelId),
            ];
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
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
                ->map(function ($payment) {
                    return [
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
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (\Exception $e) {
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
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'tx_ref' => $payment->tx_ref,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'customer_name' => $payment->customer_name,
                        'email' => $payment->email,
                        'status' => $payment->status,
                        'type' => $payment->reservation_id ? 'Reservation' : 'Restaurant Order',
                        'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (\Exception $e) {
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
                ->map(function ($payment) {
                    return [
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
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
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
            $last7Days = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $revenue = Payment::whereDate('paid_at', $date)
                    ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
                    ->sum('amount');

                $last7Days[] = [
                    'date' => $date,
                    'label' => now()->subDays($i)->format('D'),
                    'revenue' => (float) $revenue,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $last7Days,
            ]);
        } catch (\Exception $e) {
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
                ->map(function ($item) {
                    return [
                        'method' => $item->payment_method ?? 'Unknown',
                        'count' => $item->count,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $methods,
            ]);
        } catch (\Exception $e) {
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
                ->map(function ($payment) {
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
                'data' => $refunds,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch refund requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getTodayRevenue(?string $hotelId): float
    {
        return (float) Payment::whereDate('paid_at', today())
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->sum('amount');
    }

    private function getWeeklyRevenue(?string $hotelId): float
    {
        return (float) Payment::whereBetween('paid_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->sum('amount');
    }

    private function getMonthlyRevenue(?string $hotelId): float
    {
        return (float) Payment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->sum('amount');
    }

    private function getPendingPaymentsCount(?string $hotelId): int
    {
        return Payment::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIALIZED])
            ->count();
    }

    private function getCompletedPaymentsCount(?string $hotelId): int
    {
        return Payment::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->count();
    }

    private function getFailedPaymentsCount(?string $hotelId): int
    {
        return Payment::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->where('status', Payment::STATUS_FAILED)
            ->count();
    }

    private function getRefundRequestsCount(?string $hotelId): int
    {
        return Payment::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->where('status', Payment::STATUS_REFUNDED)
            ->count();
    }

    private function getTotalTransactionsCount(?string $hotelId): int
    {
        return Payment::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->count();
    }
}
