<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagerNotificationRequest;
use App\Http\Requests\UpdateManagerNotificationRequest;
use App\Http\Resources\ManagerActivityLogResource;
use App\Http\Resources\ManagerNotificationResource;
use App\Models\ManagerNotification;
use App\Services\Manager\ManagerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function __construct(
        protected ManagerService $service
    ) {}

    public function activities(Request $request): JsonResponse
    {
        try {
            $activities = $this->service->activityLogs();
            
            return response()->json([
                'success' => true,
                'data' => ManagerActivityLogResource::collection($activities),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load activities', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load activities: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function notifications(Request $request): AnonymousResourceCollection
    {
        return ManagerNotificationResource::collection(
            $this->service->notifications()
        );
    }

    public function storeNotification(StoreManagerNotificationRequest $request): ManagerNotificationResource
    {
        $notification = $this->service->createNotification($request->validated());

        return new ManagerNotificationResource($notification);
    }

    public function updateNotification(
        UpdateManagerNotificationRequest $request,
        ManagerNotification $notification
    ): ManagerNotificationResource {
        $notification = $this->service->updateNotification(
            $notification,
            $request->validated()
        );

        return new ManagerNotificationResource($notification);
    }

    public function destroyNotification(ManagerNotification $notification): JsonResponse
    {
        $this->service->deleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.'
        ]);
    }

    public function markAsRead(ManagerNotification $notification): ManagerNotificationResource
    {
        $notification = $this->service->markAsRead($notification);

        return new ManagerNotificationResource($notification);
    }
}
