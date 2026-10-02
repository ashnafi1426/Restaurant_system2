<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReviewNotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display a listing of review notifications for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = (string) $request->user()->id;
            $perPage = (int) $request->query('per_page', 20);

            $notifications = $this->notificationService->getUserNotifications($userId, $perPage);

            return response()->json($notifications);
        } catch (Throwable $e) {
            Log::error('Error retrieving review notifications', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve notifications',
            ], 500);
        }
    }

    /**
     * Get unread review notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $userId = (string) $request->user()->id;
            $count = $this->notificationService->getUnreadCount($userId);

            return response()->json([
                'data' => [
                    'unread_count' => $count,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Error retrieving unread review count', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve unread count',
            ], 500);
        }
    }

    /**
     * Mark a review notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $success = $this->notificationService->markAsRead($id);

            if (!$success) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Notification not found',
                ], 404);
            }

            return response()->json([
                'message' => 'Notification marked as read',
            ]);
        } catch (Throwable $e) {
            Log::error('Error marking review notification as read', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to mark notification as read',
            ], 500);
        }
    }
}
