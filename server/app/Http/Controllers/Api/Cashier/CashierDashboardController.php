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
    /**
     * Get cashier dashboard statistics
     */
    public function index(): JsonResponse
    {
        try {
            $stats = [
                'today_revenue' => $this->getTodayRevenue(),
                'weekly_revenue' => $this->getWeeklyRevenue(),
                'monthly_revenue' => $this->getMonthlyRevenue(),
                'pending_payments' => $this->getPendingPaymentsCount(),
                'completed_payments' => $this->getCompletedPaymentsCount(),
                'failed_payments' => $this->getFailedPaymentsCount(),
                'refund_requests' => $this->getRefundRequestsCount(),
                'total_transactions' => $this->getTotalTransactionsCount(),
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

    /**
     * Get recent payments
     */
    public function recentPayments(): JsonResponse
    {
        try {
            $payments = Payment::with(['guest', 'reservation', 'order'])
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

    /**
     * Get pending payments
     */
    public function pendingPayments(): JsonResponse
    {
        try {
            $payments = Payment::with(['guest', 'reservation', 'order'])
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

    /**
     * Get recent transactions
     */
    public function recentTransactions(): JsonResponse
    {
        try {
            $transactions = Payment::with(['guest', 'reservation', 'order'])
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

    /**
     * Get revenue chart data (last 7 days)
     */
    public function revenueChart(): JsonResponse
    {
        try {
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $revenue = Payment::whereDate('paid_at', $date)
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

    /**
     * Get payment method distribution
     */
    public function paymentMethodChart(): JsonResponse
    {
        try {
            $methods = Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
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

    /**
     * Get refund requests (payments marked as refunded)
     */
    public function refundRequests(): JsonResponse
    {
        try {
            $refunds = Payment::with(['guest', 'reservation', 'order'])
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

    // ========================================================================
    // Private Helper Methods
    // ========================================================================

    private function getTodayRevenue(): float
    {
        return (float) Payment::whereDate('paid_at', today())
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->sum('amount');
    }

    private function getWeeklyRevenue(): float
    {
        return (float) Payment::whereBetween('paid_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->sum('amount');
    }

    private function getMonthlyRevenue(): float
    {
        return (float) Payment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->sum('amount');
    }

    private function getPendingPaymentsCount(): int
    {
        return Payment::whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIALIZED])
            ->count();
    }

    private function getCompletedPaymentsCount(): int
    {
        return Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->count();
    }

    private function getFailedPaymentsCount(): int
    {
        return Payment::where('status', Payment::STATUS_FAILED)
            ->count();
    }

    private function getRefundRequestsCount(): int
    {
        return Payment::where('status', Payment::STATUS_REFUNDED)
            ->count();
    }

    private function getTotalTransactionsCount(): int
    {
        return Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])
            ->count();
    }
}
