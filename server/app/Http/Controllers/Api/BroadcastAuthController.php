<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BroadcastAuthController
 * 
 * Handles WebSocket channel authorization for Laravel Echo.
 * Supports both authenticated users and public guest order tracking.
 */
class BroadcastAuthController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Authenticate WebSocket channel access.
     */
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $channelName = $request->input('channel_name');
            $socketId = $request->input('socket_id');

            if (!$channelName || !$socketId) {
                return response()->json(['error' => 'Missing channel_name or socket_id'], 400);
            }

            Log::info('[BroadcastAuth] Channel auth request', [
                'channel' => $channelName,
                'socket_id' => $socketId,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip()
            ]);

            // Handle different channel types
            if (str_starts_with($channelName, 'private-orders.')) {
                return $this->authorizeOrderChannel($request, $channelName, $socketId);
            }

            if (str_starts_with($channelName, 'private-hotel.')) {
                return $this->authorizeHotelChannel($request, $channelName, $socketId);
            }

            if (str_starts_with($channelName, 'private-payments.')) {
                return $this->authorizePaymentsChannel($request, $channelName, $socketId);
            }

            // Default: deny unknown channels
            Log::warning('[BroadcastAuth] Unknown channel type', ['channel' => $channelName]);
            return response()->json(['error' => 'Channel not found'], 404);

        } catch (\Exception $e) {
            Log::error('[BroadcastAuth] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Authorization failed'], 500);
        }
    }

    /**
     * Authorize access to order-specific channels: orders.{hotelId}.{orderId}
     */
    protected function authorizeOrderChannel(Request $request, string $channelName, string $socketId): JsonResponse
    {
        // Extract hotelId and orderId from channel name
        // Format: private-orders.{hotelId}.{orderId}
        $parts = explode('.', str_replace('private-', '', $channelName));
        
        if (count($parts) !== 3 || $parts[0] !== 'orders') {
            return response()->json(['error' => 'Invalid channel format'], 400);
        }

        $hotelId = $parts[1];
        $orderId = $parts[2];

        // Validate that the order exists and belongs to the specified hotel
        // Load room and table relationships for QR token validation
        $order = Order::withoutGlobalScopes()
            ->with(['room', 'table'])
            ->where('id', $orderId)
            ->orWhere('order_number', $orderId)
            ->first();

        if (!$order) {
            Log::warning('[BroadcastAuth] Order not found', [
                'channel' => $channelName,
                'order_id' => $orderId
            ]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // CRITICAL: Validate hotel_id for multi-tenant security
        if ($order->hotel_id !== $hotelId) {
            Log::warning('[BroadcastAuth] Hotel ID mismatch - unauthorized access attempt', [
                'channel' => $channelName,
                'order_hotel_id' => $order->hotel_id,
                'requested_hotel_id' => $hotelId,
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if this is a guest request with QR token (from Echo authorizer)
        $qrToken = $request->input('qr_token') ?? $request->header('X-QR-Token');
        
        if ($qrToken) {
            // Validate QR token matches the order's room/table
            $isValidToken = false;
            
            if ($order->room && $order->room->qr_token === $qrToken) {
                $isValidToken = true;
            } elseif ($order->table && $order->table->qr_token === $qrToken) {
                $isValidToken = true;
            } elseif ($order->hotel_id && \App\Models\RestaurantTable::withoutGlobalScopes()->where('hotel_id', $order->hotel_id)->where('qr_token', $qrToken)->exists()) {
                $isValidToken = true;
            } elseif ($order->hotel_id && \App\Models\Room::withoutGlobalScopes()->where('hotel_id', $order->hotel_id)->where('qr_token', $qrToken)->exists()) {
                $isValidToken = true;
            } elseif (\App\Models\Payment::withoutGlobalScopes()->where('order_id', $order->id)->where('metadata->qr_token', $qrToken)->exists()) {
                $isValidToken = true;
            }
            
            if (!$isValidToken) {
                // Check if there's an authenticated user - staff can view any order
                $user = $request->user();
                if (!$user) {
                    Log::warning('[BroadcastAuth] Invalid QR token for guest WebSocket subscription', [
                        'channel' => $channelName,
                        'order_id' => $order->id,
                        'ip' => $request->ip()
                    ]);
                    return response()->json(['error' => 'Invalid QR token'], 403);
                }
                
                // Authenticated user with invalid QR token - treat as staff access
                Log::info('[BroadcastAuth] Order channel authorized for authenticated staff user', [
                    'channel' => $channelName,
                    'order_id' => $order->id,
                    'hotel_id' => $order->hotel_id,
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
            } else {
                Log::info('[BroadcastAuth] Guest WebSocket channel authorized via QR token', [
                    'channel' => $channelName,
                    'order_id' => $order->id,
                    'hotel_id' => $order->hotel_id
                ]);
            }
        } else {
            // No QR token - must be authenticated user (staff viewing orders)
            $user = $request->user();
            if (!$user) {
                Log::warning('[BroadcastAuth] No QR token and no authenticated user', [
                    'channel' => $channelName,
                    'order_id' => $order->id,
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            Log::info('[BroadcastAuth] Order channel authorized for authenticated user', [
                'channel' => $channelName,
                'order_id' => $order->id,
                'hotel_id' => $order->hotel_id,
                'user_id' => $user->id
            ]);
        }

        // Generate channel authorization signature
        $auth = $this->generateChannelAuth($channelName, $socketId);

        return response()->json([
            'auth' => $auth,
            'channel_data' => null
        ]);
    }

    /**
     * Authorize access to hotel-wide channels: hotel.{hotelId}.{scope}
     */
    protected function authorizeHotelChannel(Request $request, string $channelName, string $socketId): JsonResponse
    {
        // Extract hotelId and scope from channel name
        // Format: private-hotel.{hotelId}.{scope} (kitchen, waiters, orders)
        $parts = explode('.', str_replace('private-', '', $channelName));
        
        if (count($parts) !== 3 || $parts[0] !== 'hotel') {
            return response()->json(['error' => 'Invalid channel format'], 400);
        }

        $hotelId = $parts[1];
        $scope = $parts[2]; // kitchen, waiters, orders

        // These channels require authentication
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        // Validate user belongs to the hotel
        $hasAccess = false;
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            $hasAccess = true;
        } elseif (method_exists($user, 'belongsToHotel') && $user->belongsToHotel($hotelId)) {
            $hasAccess = true;
        } elseif (isset($user->hotel_id) && $user->hotel_id === $hotelId) {
            $hasAccess = true;
        } elseif (\App\Models\HotelUser::where('user_id', $user->id)->where('hotel_id', $hotelId)->where('is_active', true)->exists()) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            Log::warning('[BroadcastAuth] Hotel channel unauthorized', [
                'channel' => $channelName,
                'requested_hotel_id' => $hotelId,
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('[BroadcastAuth] Hotel channel authorized', [
            'channel' => $channelName,
            'user_id' => $user->id,
            'hotel_id' => $hotelId,
            'scope' => $scope
        ]);

        $auth = $this->generateChannelAuth($channelName, $socketId);

        return response()->json([
            'auth' => $auth,
            'channel_data' => null
        ]);
    }

    /**
     * Authorize access to payment channels: payments.{hotelId}
     */
    protected function authorizePaymentsChannel(Request $request, string $channelName, string $socketId): JsonResponse
    {
        $hotelId = str_replace('private-payments.', '', $channelName);
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $hasAccess = false;
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            $hasAccess = true;
        } elseif (method_exists($user, 'belongsToHotel') && $user->belongsToHotel($hotelId)) {
            $hasAccess = true;
        } elseif (isset($user->hotel_id) && $user->hotel_id === $hotelId) {
            $hasAccess = true;
        } elseif (\App\Models\HotelUser::where('user_id', $user->id)->where('hotel_id', $hotelId)->where('is_active', true)->exists()) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            Log::warning('[BroadcastAuth] Payments channel unauthorized', [
                'channel' => $channelName,
                'requested_hotel_id' => $hotelId,
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('[BroadcastAuth] Payments channel authorized', [
            'channel' => $channelName,
            'user_id' => $user->id,
            'hotel_id' => $hotelId
        ]);

        $auth = $this->generateChannelAuth($channelName, $socketId);

        return response()->json([
            'auth' => $auth,
            'channel_data' => null
        ]);
    }

    /**
     * Generate channel authorization signature for Pusher protocol.
     */
    protected function generateChannelAuth(string $channelName, string $socketId): string
    {
        $appKey = config('broadcasting.connections.reverb.key');
        $appSecret = config('broadcasting.connections.reverb.secret');

        $stringToSign = $socketId . ':' . $channelName;
        $signature = hash_hmac('sha256', $stringToSign, $appSecret);

        return $appKey . ':' . $signature;
    }
}