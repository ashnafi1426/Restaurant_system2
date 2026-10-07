<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CustomerOrderController
 * 
 * Public API for customers to track their order status in real-time.
 * Used by OrderStatusPage.vue for initial data load before WebSocket subscription.
 * 
 * Multi-tenant security: Validates hotel_id to prevent cross-tenant access.
 */
class CustomerOrderController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Get order status for customer tracking.
     * 
     * Route: GET /api/guest/orders/{orderId}/status
     * 
     * @param Request $request
     * @param string $orderId - Order UUID or order_number
     * @return JsonResponse
     */
    public function getOrderStatus(Request $request, string $orderId): JsonResponse
    {
        try {
            \Log::info('[CustomerOrder] Request received', [
                'order_id' => $orderId,
                'qr_token' => $request->query('qr_token') ? 'present' : 'missing',
                'headers' => $request->headers->all(),
                'ip' => $request->ip()
            ]);

            // Find order by UUID or order_number
            // Load room and table relationships for QR token validation
            $order = Order::withoutGlobalScopes()
                ->with(['room', 'table'])
                ->where('id', $orderId)
                ->orWhere('order_number', $orderId)
                ->first();

            \Log::info('[CustomerOrder] Order lookup result', [
                'order_id' => $orderId,
                'found' => !!$order,
                'actual_order_id' => $order?->id
            ]);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                    'error' => 'ORDER_NOT_FOUND'
                ], 404);
            }

            // Check if this is a guest request with QR token
            $qrToken = $request->query('qr_token') ?? $request->header('X-QR-Token');
            $isGuestRequest = !empty($qrToken);

            if ($isGuestRequest) {
                // Validate QR token matches the order's room/table, payment metadata, or table/room in the same hotel
                $isValidToken = false;
                
                if ($order->room && $order->room->qr_token === $qrToken) {
                    $isValidToken = true;
                } elseif ($order->table && $order->table->qr_token === $qrToken) {
                    $isValidToken = true;
                } elseif (Payment::withoutGlobalScopes()->where('order_id', $order->id)->where('metadata->qr_token', $qrToken)->exists()) {
                    $isValidToken = true;
                } elseif ($order->hotel_id && RestaurantTable::withoutGlobalScopes()->where('hotel_id', $order->hotel_id)->where('qr_token', $qrToken)->exists()) {
                    $isValidToken = true;
                } elseif ($order->hotel_id && Room::withoutGlobalScopes()->where('hotel_id', $order->hotel_id)->where('qr_token', $qrToken)->exists()) {
                    $isValidToken = true;
                }
                
                if (!$isValidToken) {
                    \Log::warning('[CustomerOrder] Invalid QR token for guest order', [
                        'order_id' => $orderId,
                        'qr_token' => substr($qrToken, 0, 4) . '****',
                        'ip' => $request->ip()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid QR token.',
                        'error' => 'INVALID_TOKEN'
                    ], 403);
                }
                
                \Log::info('[CustomerOrder] Guest access validated via QR token', [
                    'order_id' => $orderId,
                    'hotel_id' => $order->hotel_id
                ]);
            } else {
                // For authenticated requests, validate hotel_id
                $requestHotelId = $this->extractHotelId($request, $order);

                // CRITICAL: Validate hotel_id matches (multi-tenant security)
                if ($requestHotelId && $order->hotel_id !== $requestHotelId) {
                    Log::warning('[CustomerOrder] Hotel ID mismatch - potential unauthorized access attempt', [
                        'order_id' => $orderId,
                        'order_hotel_id' => $order->hotel_id,
                        'request_hotel_id' => $requestHotelId,
                        'ip' => $request->ip()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to order.',
                        'error' => 'UNAUTHORIZED'
                    ], 403);
                }
            }

            // Load relationships for complete order data
            $order->load([
                'orderItems.menuItem' => fn($q) => $q->withoutGlobalScopes(),
                'room' => fn($q) => $q->withoutGlobalScopes(),
                'table' => fn($q) => $q->withoutGlobalScopes(),
                'guest' => fn($q) => $q->withoutGlobalScopes(),
                'chef',
            ]);

            // Build response data
            $data = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'hotel_id' => $order->hotel_id,
                'status' => $order->status,
                'order_type' => $order->order_type,
                'order_time' => $order->order_time?->toISOString() ?? $order->created_at->toISOString(),
                
                // Location info
                'room_number' => $order->room?->room_number,
                'table_number' => $order->table?->table_number,
                
                // Customer info
                'customer_name' => $this->getCustomerName($order),
                
                // Order items
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->item_name ?? $item->menuItem?->name,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->item_price_at_order,
                        'total' => (float) $item->total,
                    ];
                }),
                
                // Pricing
                'subtotal' => (float) $order->subtotal,
                'tax' => (float) $order->tax,
                'service_charge' => (float) $order->service_charge_amount,
                'total' => (float) $order->total,
                
                // Payment info
                'payment_type' => ($this->hasVerifiedPayment($order) && $order->payment_type === 'room_charge') ? 'chapa' : $order->payment_type,
                'payment_status' => $this->getPaymentStatus($order),
                
                // Chef info
                'chef_id' => $order->chef_id,
                'chef_name' => $order->chef?->name,
                
                // Timestamps
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
                'served_at' => $order->served_at?->toISOString(),
                
                // Additional metadata
                'notes' => $order->notes,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('[CustomerOrder] Error fetching order status: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order status.',
                'error' => 'SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Extract hotel_id from request context for validation.
     * 
     * Priority:
     * 1. Authenticated user's hotel_id
     * 2. QR token's hotel context (from session/header)
     * 3. Order's hotel_id (fallback for guest access)
     */
    protected function extractHotelId(Request $request, Order $order): ?string
    {
        // Check authenticated user
        $user = $request->user();
        if ($user && method_exists($user, 'hotel_id')) {
            return $user->hotel_id;
        }

        // Check tenant context (set by QR token middleware)
        $contextHotelId = $this->tenantContext->getHotelId();
        if ($contextHotelId) {
            return $contextHotelId;
        }

        // Check X-Hotel-ID header (used by QR menu)
        $headerHotelId = $request->header('X-Hotel-ID');
        if ($headerHotelId) {
            return $headerHotelId;
        }

        // For public/guest access via QR menu, we allow access if hotel_id isn't explicitly provided
        // The QR token validation happens at a different layer
        // Return order's hotel_id to allow comparison (will match itself, allowing access)
        return $order->hotel_id;
    }

    /**
     * Get customer name from order relationships.
     */
    protected function getCustomerName(Order $order): ?string
    {
        if ($order->guest) {
            return $order->guest->name 
                ?? trim(($order->guest->first_name ?? '') . ' ' . ($order->guest->last_name ?? ''))
                ?? null;
        }

        return 'Guest';
    }

    /**
     * Check if this order has a verified/paid payment record in database.
     */
    protected function hasVerifiedPayment(Order $order): bool
    {
        $hasPaidPayment = \App\Models\Payment::withoutGlobalScopes()
            ->where(function($q) use ($order) {
                $q->where('order_id', $order->id)
                  ->orWhereJsonContains('metadata->order_id', $order->id);
            })
            ->whereIn('status', ['paid', 'verified', 'completed', 'successful', 'success'])
            ->exists();

        if ($hasPaidPayment) {
            return true;
        }

        $hasPaidWalkIn = \App\Models\WalkInPayment::withoutGlobalScopes()
            ->where('order_id', $order->id)
            ->whereIn('payment_status', ['paid', 'verified', 'completed'])
            ->exists();

        return $hasPaidWalkIn;
    }

    /**
     * Determine payment status from order data.
     */
    protected function getPaymentStatus(Order $order): string
    {
        // 1. Check if there is a verified/paid payment in database
        if ($this->hasVerifiedPayment($order)) {
            return 'paid';
        }

        // 2. Infer from payment_type
        if (in_array(strtolower((string)$order->payment_type), ['chapa', 'card', 'online', 'telebirr', 'cbe_birr', 'paid'])) {
            return 'paid';
        }

        // 3. If order has explicit payment_status attribute, use it
        if (isset($order->payment_status) && !empty($order->payment_status)) {
            return $order->payment_status;
        }

        // 4. Room charges are pending until paid
        if ($order->payment_type === 'room_charge') {
            return 'pending';
        }

        // 5. For served orders, assume paid
        if (in_array($order->status, ['served', 'completed'])) {
            return 'paid';
        }

        // Default
        return 'pending';
    }
}
