<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierPaymentController extends Controller
{
    /**
     * Get all payments with filters, search, sorting and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Payment::with(['guest', 'reservation', 'order']);

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('tx_ref', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('chapa_transaction_id', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment provider
            if ($request->filled('provider')) {
                $query->where('payment_provider', $request->provider);
            }

            // Filter by type (reservation or order)
            if ($request->filled('type')) {
                if ($request->type === 'reservation') {
                    $query->whereNotNull('reservation_id');
                } elseif ($request->type === 'order') {
                    $query->whereNotNull('order_id');
                }
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Quick filters
            if ($request->filled('filter')) {
                switch ($request->filter) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year);
                        break;
                    case 'paid':
                        $query->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_VERIFIED]);
                        break;
                    case 'pending':
                        $query->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIALIZED]);
                        break;
                    case 'failed':
                        $query->where('status', Payment::STATUS_FAILED);
                        break;
                    case 'refunded':
                        $query->where('status', Payment::STATUS_REFUNDED);
                        break;
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            // Transform data
            $payments->getCollection()->transform(function ($payment) {
                return [
                    'id' => $payment->id,
                    'tx_ref' => $payment->tx_ref,
                    'chapa_transaction_id' => $payment->chapa_transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'formatted_amount' => $payment->formatted_amount,
                    'customer_name' => $payment->customer_name,
                    'email' => $payment->email,
                    'phone' => $payment->phone,
                    'status' => $payment->status,
                    'payment_provider' => $payment->payment_provider,
                    'payment_method' => $payment->payment_method,
                    'type' => $payment->reservation_id ? 'Reservation' : ($payment->order_id ? 'Restaurant Order' : 'Unknown'),
                    'reference_id' => $payment->reservation_id ?? $payment->order_id,
                    'guest' => $payment->guest ? [
                        'id' => $payment->guest->id,
                        'name' => $payment->guest->name,
                        'email' => $payment->guest->email,
                    ] : null,
                    'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                    'verified_at' => $payment->verified_at?->format('Y-m-d H:i:s'),
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single payment details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $payment = Payment::with(['guest', 'reservation.room', 'order.orderItems.menuItem'])
                ->findOrFail($id);

            $data = [
                'id' => $payment->id,
                'tx_ref' => $payment->tx_ref,
                'chapa_transaction_id' => $payment->chapa_transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => $payment->formatted_amount,
                'first_name' => $payment->first_name,
                'last_name' => $payment->last_name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                'status' => $payment->status,
                'payment_provider' => $payment->payment_provider,
                'payment_method' => $payment->payment_method,
                'checkout_url' => $payment->checkout_url,
                'callback_url' => $payment->callback_url,
                'return_url' => $payment->return_url,
                'type' => $payment->reservation_id ? 'Reservation' : ($payment->order_id ? 'Restaurant Order' : 'Unknown'),
                'guest' => $payment->guest ? [
                    'id' => $payment->guest->id,
                    'name' => $payment->guest->name,
                    'email' => $payment->guest->email,
                    'phone' => $payment->guest->phone,
                ] : null,
                'reservation' => $payment->reservation ? [
                    'id' => $payment->reservation->id,
                    'check_in_date' => $payment->reservation->check_in_date,
                    'check_out_date' => $payment->reservation->check_out_date,
                    'number_of_guests' => $payment->reservation->number_of_guests,
                    'room' => $payment->reservation->room ? [
                        'room_number' => $payment->reservation->room->room_number,
                        'floor' => $payment->reservation->room->floor,
                    ] : null,
                ] : null,
                'order' => $payment->order ? [
                    'id' => $payment->order->id,
                    'order_number' => $payment->order->id,
                    'items_count' => $payment->order->orderItems->count(),
                    'status' => $payment->order->status,
                ] : null,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                'verified_at' => $payment->verified_at?->format('Y-m-d H:i:s'),
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $payment->updated_at->format('Y-m-d H:i:s'),
                'metadata' => $payment->metadata,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment details',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Mark payment as refunded
     */
    public function refund(string $id, Request $request): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($id);

            // Only paid or verified payments can be refunded
            if (!in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_VERIFIED])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only paid payments can be refunded',
                ], 400);
            }

            $payment->markAsRefunded();

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as refunded successfully',
                'data' => [
                    'id' => $payment->id,
                    'status' => $payment->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refund payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
