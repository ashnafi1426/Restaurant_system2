<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    /**
     * Get active customer orders for cashier billing and clearing.
     */
    public function activeOrders(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $filter = $request->query('filter', 'all');
            $search = $request->query('search', '');

            $query = Order::with([
                'guest',
                'room',
                'table',
                'orderItems.menuItem',
                'payments'
            ])
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId));

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('table', fn($tq) => $tq->where('table_number', 'like', "%{$search}%"))
                      ->orWhereHas('guest', fn($gq) => $gq->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                });
            }

            if ($filter === 'paid') {
                $query->whereHas('payments', fn($pq) => $pq->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED]));
            } elseif ($filter === 'unpaid') {
                $query->whereDoesntHave('payments', fn($pq) => $pq->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED]))
                      ->where('status', '!=', Order::STATUS_CANCELLED);
            } elseif ($filter === 'cleared') {
                $query->where('status', Order::STATUS_SERVED);
            } elseif ($filter === 'pending_clear') {
                $query->where('status', '!=', Order::STATUS_SERVED)
                      ->where('status', '!=', Order::STATUS_CANCELLED);
            }

            $orders = $query->latest('created_at')->limit(50)->get()->map(function ($order) {
                $successfulPayment = $order->payments->first(fn($p) => in_array($p->status, [Payment::STATUS_PAID, Payment::STATUS_VERIFIED]));
                $pendingPayment = $order->payments->first(fn($p) => in_array($p->status, [Payment::STATUS_PENDING, Payment::STATUS_INITIALIZED]));

                $paymentStatus = 'unpaid';
                if ($successfulPayment) {
                    $paymentStatus = 'paid';
                } elseif ($pendingPayment) {
                    $paymentStatus = 'pending';
                }

                $guestName = 'Walk-in Guest';
                if ($order->guest) {
                    $guestName = trim(($order->guest->first_name ?? '') . ' ' . ($order->guest->last_name ?? '')) ?: ($order->guest->name ?? 'Guest');
                }

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type ?? 'dine_in',
                    'status' => $order->status,
                    'is_cleared' => $order->status === Order::STATUS_SERVED,
                    'served_at' => $order->served_at?->format('Y-m-d H:i:s'),
                    'order_time' => ($order->order_time ?? $order->created_at)->format('Y-m-d H:i:s'),
                    'table_id' => $order->table_id,
                    'table_number' => $order->table ? ($order->table->table_number ?? 'Table') : null,
                    'table_status' => $order->table ? $order->table->status : null,
                    'room_id' => $order->room_id,
                    'room_number' => $order->room ? $order->room->room_number : null,
                    'guest_name' => $guestName,
                    'guest_phone' => $order->guest?->phone,
                    'notes' => $order->notes,
                    'subtotal' => (float) $order->subtotal,
                    'tax' => (float) $order->tax,
                    'service_charge' => (float) $order->service_charge_amount,
                    'discount' => (float) $order->discount,
                    'total' => (float) $order->total,
                    'payment_type' => $order->payment_type,
                    'payment_status' => $paymentStatus,
                    'paid_at' => $successfulPayment?->paid_at?->format('Y-m-d H:i:s'),
                    'payment_method' => $successfulPayment?->payment_method ?? $order->payment_type ?? 'cash',
                    'tx_ref' => $successfulPayment?->tx_ref ?? $pendingPayment?->tx_ref,
                    'items_count' => $order->orderItems->sum('quantity'),
                    'items' => $order->orderItems->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->item_name ?? $item->menuItem?->name ?? 'Menu Item',
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->item_price_at_order,
                        'total' => (float) $item->total,
                    ]),
                ];
            });

            $counts = [
                'total_active' => Order::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING, Order::STATUS_READY])
                    ->count(),
                'paid_orders' => Order::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->whereHas('payments', fn($pq) => $pq->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED]))
                    ->count(),
                'unpaid_orders' => Order::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING, Order::STATUS_READY])
                    ->whereDoesntHave('payments', fn($pq) => $pq->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED]))
                    ->count(),
                'cleared_today' => Order::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->where('status', Order::STATUS_SERVED)
                    ->whereDate('served_at', today())
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $orders,
                'counts' => $counts,
            ]);
        } catch (Exception $e) {
            Log::error('Cashier active orders error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear an order after payment and release the associated restaurant table.
     */
    public function clearOrder(Request $request, string $id): JsonResponse
    {
        try {
            $order = Order::with(['table', 'payments', 'guest'])->findOrFail($id);

            $markAsPaid = $request->boolean('mark_as_paid', false);
            $paymentMethod = $request->input('payment_method', 'cash');

            return DB::transaction(function () use ($order, $markAsPaid, $paymentMethod) {
                // 1. Check or record payment
                $hasSuccessfulPayment = $order->payments()->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])->exists();

                if ($markAsPaid || !$hasSuccessfulPayment) {
                    $payment = $order->payments()->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIALIZED])->first();
                    if ($payment) {
                        $payment->update([
                            'status' => Payment::STATUS_PAID,
                            'payment_method' => $paymentMethod,
                            'paid_at' => now(),
                        ]);
                    } else {
                        Payment::create([
                            'hotel_id' => $order->hotel_id,
                            'tx_ref' => 'TX-' . strtoupper(Str::random(10)),
                            'order_id' => $order->id,
                            'guest_id' => $order->guest_id,
                            'amount' => $order->total,
                            'currency' => 'ETB',
                            'customer_name' => $order->guest ? trim(($order->guest->first_name ?? '') . ' ' . ($order->guest->last_name ?? '')) : 'Guest',
                            'email' => $order->guest?->email ?? 'cashier@hotel.com',
                            'status' => Payment::STATUS_PAID,
                            'payment_provider' => 'cashier',
                            'payment_method' => $paymentMethod,
                            'paid_at' => now(),
                        ]);
                    }
                    $order->update(['payment_type' => $paymentMethod]);
                }

                // 2. Mark order status as SERVED
                $order->update([
                    'status' => Order::STATUS_SERVED,
                    'served_at' => $order->served_at ?? now(),
                ]);

                // 3. Complete any delivery task associated with this order
                try {
                    \App\Models\DeliveryTask::withoutGlobalScopes()
                        ->where('order_id', $order->id)
                        ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery', 'waiting_assignment', 'pending'])
                        ->update([
                            'status' => 'delivered',
                            'delivered_at' => now(),
                        ]);
                } catch (\Throwable $te) {
                    Log::warning('DeliveryTask update warning on clearOrder: ' . $te->getMessage());
                }

                // 4. Free up the table if one is assigned
                $tableNumber = null;
                if ($order->table_id && $order->table) {
                    $tableNumber = $order->table->table_number;
                    $order->table->update([
                        'status' => \App\Models\RestaurantTable::STATUS_AVAILABLE,
                    ]);
                    Log::info("Table {$tableNumber} released to available status upon clearing order #{$order->order_number}");
                }

                return response()->json([
                    'success' => true,
                    'message' => "Order #{$order->order_number} cleared successfully" . ($tableNumber ? " and Table {$tableNumber} is now available." : "."),
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'table_number' => $tableNumber,
                        'table_released' => (bool) $tableNumber,
                        'payment_status' => 'paid',
                    ],
                ]);
            });
        } catch (Exception $e) {
            Log::error('Cashier clear order error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear order: ' . $e->getMessage(),
            ], 500);
        }
    }
}
