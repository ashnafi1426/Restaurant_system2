<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Models\WaiterNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WaiterNotificationController extends Controller
{
    public function getNotifications(): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Waiter not found',
            ]);
        }
        
        $notifications = WaiterNotification::where('waiter_id', $waiter->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }
        
        $unreadCount = WaiterNotification::where('waiter_id', $waiter->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    public function getUnread(): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
        
        $notifications = WaiterNotification::where('waiter_id', $waiter->id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function markAsRead($id): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter not found',
            ], 404);
        }
        
        $notification = WaiterNotification::where('waiter_id', $waiter->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification,
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter not found',
            ], 404);
        }
        
        WaiterNotification::where('waiter_id', $waiter->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    public function deleteNotification($id): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter not found',
            ], 404);
        }
        
        $notification = WaiterNotification::where('waiter_id', $waiter->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    public function deleteAll(): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter not found',
            ], 404);
        }
        
        WaiterNotification::where('waiter_id', $waiter->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications deleted',
        ]);
    }

    public function getStats(): JsonResponse
    {
        $user = Auth::user();
        
        $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_count' => 0,
                    'unread_count' => 0,
                    'read_count' => 0,
                    'type_distribution' => [],
                ],
            ]);
        }
        
        $totalCount = WaiterNotification::where('waiter_id', $waiter->id)->count();
        $unreadCount = WaiterNotification::where('waiter_id', $waiter->id)
            ->where('is_read', false)
            ->count();

        $typeDistribution = WaiterNotification::where('waiter_id', $waiter->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $totalCount,
                'unread_count' => $unreadCount,
                'read_count' => $totalCount - $unreadCount,
                'type_distribution' => $typeDistribution,
            ],
        ]);
    }
}
