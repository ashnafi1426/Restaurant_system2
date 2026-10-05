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
        $order = Order::withoutGlobalScopes()
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

        // For guest orders (QR menu), allow access without authentication
        // The security is provided by the hotel_id + order_id validation above
        Log::info('[BroadcastAuth] Order channel authorized', [
            'channel' => $channelName,
            'order_id' => $order->id,
            'hotel_id' => $order->hotel_id
        ]);

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
        if ($user->hotel_id !== $hotelId) {
            Log::warning('[BroadcastAuth] Hotel channel unauthorized', [
                'channel' => $channelName,
                'user_hotel_id' => $user->hotel_id,
                'requested_hotel_id' => $hotelId,
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Additional role-based authorization could be added here
        // For now, any authenticated user from the hotel can access these channels

        Log::info('[BroadcastAuth] Hotel channel authorized', [
            'channel' => $channelName,
            'user_id' => $user->id,
            'hotel_id' => $user->hotel_id,
            'scope' => $scope
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