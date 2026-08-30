<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewNotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $perPage = $request->query('per_page', 20);
            
            $notifications = $this->notificationService->getUserNotifications($userId, $perPage);
            
            return response()->json($notifications);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve notifications',
            ], 500);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $count = $this->notificationService->getUnreadCount($userId);
            
            return response()->json([
                'data' => [
                    'unread_count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve unread count',
            ], 500);
        }
    }

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
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to mark notification as read',
            ], 500);
        }
    }
}
