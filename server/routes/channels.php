<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
| MULTI-TENANT SECURITY: All channels MUST validate hotel_id to prevent
| cross-tenant data leakage.
|
*/

/**
 * Customer Order Status Channel
 * 
 * Channel: orders.{hotelId}.{orderId}
 * 
 * Authorization:
 * - Order must exist
 * - Order's hotel_id must match {hotelId} parameter
 * - User must own the order OR be authenticated staff of that hotel
 */
Broadcast::channel('orders.{hotelId}.{orderId}', function ($user, string $hotelId, string $orderId) {
    try {
        // Find the order
        $order = Order::withoutGlobalScopes()->find($orderId);
        
        if (!$order) {
            Log::warning("[WebSocket Auth] Order not found", ['order_id' => $orderId]);
            return false;
        }
        
        // CRITICAL: Validate hotel_id matches (multi-tenant security)
        if ($order->hotel_id !== $hotelId) {
            Log::warning("[WebSocket Auth] Hotel ID mismatch", [
                'order_hotel_id' => $order->hotel_id,
                'requested_hotel_id' => $hotelId,
                'order_id' => $orderId
            ]);
            return false;
        }
        
        // If no authenticated user, allow access only if this is a guest order
        // (guest can track their own order via QR token session)
        if (!$user) {
            // For guest orders, BroadcastAuthController has already validated the QR token
            // at /api/broadcasting/auth BEFORE this closure is called. This closure is the
            // second layer of authorization after BroadcastAuthController approves the request.
            // Returning true here allows guests whose QR tokens were validated to subscribe.
            return true;
        }
        
        // Check if user owns this order (is the guest)
        if ($order->guest_id && $order->guest_id === $user->id) {
            return true;
        }
        
        // Check if user is staff of this hotel
        if ($user instanceof User) {
            // Platform admins have access to all hotels
            if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
                return true;
            }
            
            // Check if user belongs to this hotel
            if (method_exists($user, 'belongsToHotel') && $user->belongsToHotel($hotelId)) {
                return true;
            }
            
            // Check via hotel memberships
            $isMember = \App\Models\HotelUser::where('user_id', $user->id)
                ->where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->exists();
            
            if ($isMember) {
                return true;
            }
        }
        
        Log::warning("[WebSocket Auth] Unauthorized order channel access attempt", [
            'user_id' => $user->id ?? 'guest',
            'order_id' => $orderId,
            'hotel_id' => $hotelId
        ]);
        
        return false;
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Channel authorization error: " . $e->getMessage(), [
            'hotel_id' => $hotelId,
            'order_id' => $orderId,
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
});

/**
 * Hotel Kitchen Channel
 * 
 * Channel: hotel.{hotelId}.kitchen
 * 
 * Authorization:
 * - User must be authenticated
 * - User must be staff of the specified hotel
 * - User must have kitchen access (chef, kitchen_staff, manager, admin)
 */
Broadcast::channel('hotel.{hotelId}.kitchen', function ($user, string $hotelId) {
    if (!$user) {
        return false;
    }
    
    try {
        // Platform admins have access
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            return true;
        }
        
        // Check if user belongs to this hotel
        $isMember = \App\Models\HotelUser::where('user_id', $user->id)
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();
        
        if (!$isMember) {
            return false;
        }
        
        // Check if user has kitchen role
        $userRole = strtolower($user->role ?? '');
        $kitchenRoles = ['chef', 'cook', 'kitchen_staff', 'manager', 'admin'];
        
        if (in_array($userRole, $kitchenRoles)) {
            return true;
        }
        
        // Check via roles relationship
        if (method_exists($user, 'hasRole')) {
            foreach ($kitchenRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }
        
        return false;
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Kitchen channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Hotel Waiters Channel
 * 
 * Channel: hotel.{hotelId}.waiters
 * 
 * Authorization:
 * - User must be authenticated
 * - User must be a waiter or staff member of the specified hotel
 */
Broadcast::channel('hotel.{hotelId}.waiters', function ($user, string $hotelId) {
    if (!$user) {
        return false;
    }
    
    try {
        // Platform admins have access
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            return true;
        }
        
        // Check if user belongs to this hotel
        $isMember = \App\Models\HotelUser::where('user_id', $user->id)
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();
        
        if (!$isMember) {
            return false;
        }
        
        // Waiters, managers, and admins can access waiter channel
        $userRole = strtolower($user->role ?? '');
        $waiterRoles = ['waiter', 'manager', 'admin'];
        
        if (in_array($userRole, $waiterRoles)) {
            return true;
        }
        
        // Check via roles relationship
        if (method_exists($user, 'hasRole')) {
            foreach ($waiterRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }
        
        return false;
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Waiters channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Hotel Orders Channel (Dashboard)
 * 
 * Channel: hotel.{hotelId}.orders
 * 
 * Authorization:
 * - User must be authenticated staff of the specified hotel
 * - Used for hotel-wide order notifications on dashboards
 */
Broadcast::channel('hotel.{hotelId}.orders', function ($user, string $hotelId) {
    if (!$user) {
        return false;
    }
    
    try {
        // Platform admins have access
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            return true;
        }
        
        // Check if user belongs to this hotel (any staff member)
        $isMember = \App\Models\HotelUser::where('user_id', $user->id)
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();
        
        return $isMember;
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Orders channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Hotel Payments Channel (Cashier & Accounting)
 * 
 * Channel: payments.{hotelId}
 * 
 * Authorization:
 * - User must be authenticated staff of the specified hotel
 */
Broadcast::channel('payments.{hotelId}', function ($user, string $hotelId) {
    if (!$user) {
        return false;
    }
    
    try {
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            return true;
        }
        
        $isMember = \App\Models\HotelUser::where('user_id', $user->id)
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->exists();
        
        return $isMember;
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Payments channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Waiter Notification Channel
 *
 * Channel: waiter.{waiterId}
 */
Broadcast::channel('waiter.{waiterId}', function ($user, $waiterId) {
    if (!$user) {
        return false;
    }
    try {
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            return true;
        }

        $waiter = \App\Models\Waiter::find($waiterId);
        if (!$waiter) {
            return false;
        }

        // Check if user is the assigned waiter
        if ((string) $waiter->user_id === (string) $user->id) {
            return true;
        }

        // Allow manager/admin of the same hotel
        return \App\Models\HotelUser::where('user_id', $user->id)
            ->where('hotel_id', $waiter->hotel_id)
            ->where('is_active', true)
            ->exists();
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Waiter channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Delivery Task Channel
 *
 * Channel: delivery.{deliveryId}
 */
Broadcast::channel('delivery.{deliveryId}', function ($user, $deliveryId) {
    if (!$user) {
        return false;
    }
    try {
        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            return true;
        }

        $task = \App\Models\DeliveryTask::withoutGlobalScopes()->find($deliveryId);
        if (!$task) {
            return false;
        }

        return \App\Models\HotelUser::where('user_id', $user->id)
            ->where('hotel_id', $task->hotel_id)
            ->where('is_active', true)
            ->exists();
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Delivery channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Manager Notification Channel
 *
 * Channel: manager
 */
Broadcast::channel('manager', function ($user) {
    if (!$user) {
        return false;
    }
    return in_array($user->role ?? '', ['admin', 'manager', 'administrator'])
        || (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin());
});

