<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagerAnnouncementRequest;
use App\Http\Requests\StoreManagerNotificationRequest;
use App\Http\Requests\StoreWaiterRequest;
use App\Http\Requests\UpdateManagerAnnouncementRequest;
use App\Http\Requests\UpdateManagerDashboardSettingRequest;
use App\Http\Requests\UpdateManagerNotificationRequest;
use App\Http\Resources\ManagerActivityLogResource;
use App\Http\Resources\ManagerAnnouncementResource;
use App\Http\Resources\ManagerDashboardSettingResource;
use App\Http\Resources\ManagerNotificationResource;
use App\Http\Resources\ManagerReportResource;
use App\Models\ManagerAnnouncement;
use App\Models\ManagerDashboardSetting;
use App\Models\ManagerNotification;
use App\Models\User;
use App\Models\Waiter;
use App\Services\Manager\ManagerDashboardService;
use App\Services\Manager\ManagerService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ManagerController extends Controller
{
    public function __construct(
        protected ManagerService $service,
        protected ManagerDashboardService $dashboardService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->completeDashboard(),
        ]);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->statistics(),
        ]);
    }

    public function revenueSummary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->revenueSummary(),
        ]);
    }

    public function revenueChart(Request $request): JsonResponse
    {
        $period = $request->input('period', 'monthly');

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->revenueChart($period),
        ]);
    }

    public function occupancySummary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->occupancySummary(),
        ]);
    }

    public function occupancyChart(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->occupancyChart(),
        ]);
    }

    public function reservationSummary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->reservationSummary(),
        ]);
    }

    public function staff(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getStaff(),
        ]);
    }

    public function orders(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getRecentOrders(),
        ]);
    }

    public function deliveries(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getDeliveries(),
        ]);
    }

    public function housekeeping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getHousekeeping(),
        ]);
    }

    public function laundry(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getLaundry(),
        ]);
    }

    public function activities(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getActivities(),
        ]);
    }

    public function waiters(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getWaiters(),
        ]);
    }

    public function createWaiter(StoreWaiterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

            $waiter = DB::transaction(function () use ($validated, $hotelId) {
                if (empty($validated['user_id'])) {
                    if (empty($validated['first_name']) || empty($validated['last_name']) || 
                        empty($validated['email']) || empty($validated['password'])) {
                        throw new Exception('User information (first name, last name, email, password) is required when user_id is not provided.');
                    }

                    $user = User::create([
                        'hotel_id' => $hotelId,
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                        'password_hash' => Hash::make($validated['password']),
                        'role' => 'waiter',
                        'is_active' => true,
                    ]);

                    $validated['user_id'] = $user->id;
                }

                return Waiter::create([
                    'hotel_id' => $hotelId,
                    'user_id' => $validated['user_id'],
                    'section' => $validated['section'],
                    'status' => $validated['status'],
                    'shift' => $validated['shift'],
                    'experience_level' => $validated['experience_level'],
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter created successfully',
            ], 201);
        } catch (Exception $e) {
            Log::error('Create Waiter Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateWaiterStatus(Request $request, Waiter $waiter): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,on_break',
        ]);

        $waiter->update($validated);

        return response()->json([
            'success' => true,
            'data' => $waiter->load('user'),
            'message' => 'Waiter status updated successfully',
        ]);
    }

    public function deleteWaiter(Waiter $waiter): JsonResponse
    {
        $waiter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Waiter deleted successfully',
        ]);
    }

    public function notifications(): AnonymousResourceCollection
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

    public function dashboardSettings(Request $request): ManagerDashboardSettingResource
    {
        return new ManagerDashboardSettingResource(
            $this->service->dashboardSettings($request->user()->id)
        );
    }

    public function updateDashboardSettings(
        UpdateManagerDashboardSettingRequest $request,
        ManagerDashboardSetting $setting
    ): ManagerDashboardSettingResource {
        $setting = $this->service->updateDashboardSettings(
            $setting,
            $request->validated()
        );

        return new ManagerDashboardSettingResource($setting);
    }

    public function announcements(): AnonymousResourceCollection
    {
        return ManagerAnnouncementResource::collection(
            $this->service->announcements()
        );
    }

    public function storeAnnouncement(StoreManagerAnnouncementRequest $request): ManagerAnnouncementResource
    {
        $announcement = $this->service->createAnnouncement($request->validated());

        return new ManagerAnnouncementResource($announcement);
    }

    public function updateAnnouncement(
        UpdateManagerAnnouncementRequest $request,
        ManagerAnnouncement $announcement
    ): ManagerAnnouncementResource {
        $announcement = $this->service->updateAnnouncement(
            $announcement,
            $request->validated()
        );

        return new ManagerAnnouncementResource($announcement);
    }

    public function destroyAnnouncement(ManagerAnnouncement $announcement): JsonResponse
    {
        $this->service->deleteAnnouncement($announcement);

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.'
        ]);
    }

    public function reports(): AnonymousResourceCollection
    {
        return ManagerReportResource::collection(
            $this->service->reports()
        );
    }

    public function activityLogs(): AnonymousResourceCollection
    {
        return ManagerActivityLogResource::collection(
            $this->service->activityLogs()
        );
    }
}