<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);
        $userId = auth()->id();

        try {
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'count' => count($notifications),
            ]);
        } catch (Throwable $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the latest unread notification.
     */
    public function latest(): JsonResponse
    {
        $userId = auth()->id();

        try {
            $notification = Notification::where('user_id', $userId)
                ->where('read', false)
                ->orderBy('created_at', 'desc')
                ->first();

            return response()->json([
                'success' => true,
                'data' => $notification,
            ]);
        } catch (Throwable $e) {
            Log::error('Error fetching latest notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch latest notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(): JsonResponse
    {
        $userId = auth()->id();

        try {
            $count = Notification::where('user_id', $userId)
                ->where('read', false)
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (Throwable $e) {
            Log::error('Error fetching unread count: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unread count',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $userId = auth()->id();

        try {
            $notification = Notification::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $notification->update(['read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => $notification,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $userId = auth()->id();

        try {
            Notification::where('user_id', $userId)
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        } catch (Throwable $e) {
            Log::error('Error marking all as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id): JsonResponse
    {
        $userId = auth()->id();

        try {
            $notification = Notification::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all notifications for authenticated user.
     */
    public function clearAll(): JsonResponse
    {
        $userId = auth()->id();

        try {
            Notification::where('user_id', $userId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared',
            ]);
        } catch (Throwable $e) {
            Log::error('Error clearing all notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a system notification record.
     */
    public static function createNotification(
        mixed $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): ?Notification {
        try {
            return Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'reservation_id' => $data['reservation_id'] ?? null,
                'guest_name' => $data['guest_name'] ?? null,
                'room_number' => $data['room_number'] ?? null,
                'room_type' => $data['room_type'] ?? null,
                'check_in_date' => $data['check_in_date'] ?? null,
                'check_out_date' => $data['check_out_date'] ?? null,
                'read' => false,
            ]);
        } catch (Throwable $e) {
            Log::error('Error creating notification: ' . $e->getMessage());

            return null;
        }
    }
}
