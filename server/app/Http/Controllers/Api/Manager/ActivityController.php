<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerService;
use App\Http\Requests\StoreManagerNotificationRequest;
use App\Http\Requests\UpdateManagerNotificationRequest;
use App\Http\Resources\ManagerActivityLogResource;
use App\Http\Resources\ManagerNotificationResource;
use App\Models\ManagerNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager Activity & Notifications Controller
 * 
 * Handles activity logs and notifications
 */
class ActivityController extends Controller
{
    protected ManagerService $service;

    public function __construct(ManagerService $service)
    {
        $this->service = $service;
    }

    /**
     * Get recent activities
     */
    public function activities(Request $request): JsonResponse
    {
        try {
            $activities = $this->service->activityLogs();
            
            return response()->json([
                'success' => true,
                'data' => ManagerActivityLogResource::collection($activities),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load activities: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all notifications
     */
    public function notifications(Request $request)
    {
        return ManagerNotificationResource::collection(
            $this->service->notifications()
        );
    }

    /**
     * Create new notification
     */
    public function storeNotification(StoreManagerNotificationRequest $request)
    {
        $notification = $this->service->createNotification(
            $request->validated()
        );

        return new ManagerNotificationResource($notification);
    }

    /**
     * Update notification
     */
    public function updateNotification(
        UpdateManagerNotificationRequest $request,
        ManagerNotification $notification
    )
    {
        $notification = $this->service->updateNotification(
            $notification,
            $request->validated()
        );

        return new ManagerNotificationResource($notification);
    }

    /**
     * Delete notification
     */
    public function destroyNotification(
        ManagerNotification $notification
    ): JsonResponse
    {
        $this->service->deleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(ManagerNotification $notification)
    {
        $notification = $this->service->markAsRead($notification);

        return new ManagerNotificationResource($notification);
    }
}
