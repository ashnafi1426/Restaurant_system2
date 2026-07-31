<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagerAnnouncementRequest;
use App\Http\Requests\StoreManagerNotificationRequest;
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
use App\Services\Manager\ManagerService;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    protected ManagerService $service;
    protected ManagerDashboardService $dashboardService;

    public function __construct(ManagerService $service, ManagerDashboardService $dashboardService)
    {
        $this->service = $service;
        $this->dashboardService = $dashboardService;
    }
    public function dashboard(Request $request): JsonResponse
    {
        $dashboard = $this->dashboardService->completeDashboard();
        return response()->json([
            'success' => true,
            'data' => $dashboard,
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

    public function createWaiter(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|uuid|exists:users,id',
                'section' => 'required|string|max:50',
                'status' => 'required|in:active,inactive,on_break',
                'shift' => 'required|in:morning,afternoon,evening,night',
                'experience_level' => 'required|in:junior,senior,head',
                // New user fields
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:8',
            ]);

            // If user_id is not provided, create a new user
            if (empty($validated['user_id'])) {
                if (!$validated['first_name'] || !$validated['last_name'] || 
                    !$validated['email'] || !$validated['password']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User information is required for new user creation',
                    ], 422);
                }

                try {
                    $user = \App\Models\User::create([
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                        'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                        'role' => 'waiter',
                        'is_active' => true,
                    ]);

                    $validated['user_id'] = $user->id;
                } catch (\Exception $userError) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create user: ' . $userError->getMessage(),
                        'error' => $userError->getMessage(),
                    ], 500);
                }
            }

            $waiter = \App\Models\Waiter::create([
                'user_id' => $validated['user_id'],
                'section' => $validated['section'],
                'status' => $validated['status'],
                'shift' => $validated['shift'],
                'experience_level' => $validated['experience_level'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter created successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Create Waiter Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateWaiterStatus(Request $request, \App\Models\Waiter $waiter): JsonResponse
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

    public function deleteWaiter(\App\Models\Waiter $waiter): JsonResponse
    {
        $waiter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Waiter deleted successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    public function notifications()
    {
        return ManagerNotificationResource::collection(
            $this->service->notifications()
        );
    }

    public function storeNotification(
        StoreManagerNotificationRequest $request
    )
    {
        $notification = $this->service->createNotification(
            $request->validated()
        );

        return new ManagerNotificationResource(
            $notification
        );
    }

    public function updateNotification(
        UpdateManagerNotificationRequest $request,
        ManagerNotification $notification
    )
    {
        $notification = $this->service->updateNotification(
            $notification,
            $request->validated()
        );

        return new ManagerNotificationResource(
            $notification
        );
    }

    public function destroyNotification(
        ManagerNotification $notification
    ): JsonResponse
    {
        $this->service->deleteNotification(
            $notification
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.'
        ]);
    }

    public function markAsRead(
        ManagerNotification $notification
    )
    {
        $notification = $this->service->markAsRead(
            $notification
        );

        return new ManagerNotificationResource(
            $notification
        );
    }

    

    public function dashboardSettings(Request $request)
    {
        return new ManagerDashboardSettingResource(

            $this->service->dashboardSettings(
                $request->user()->id
            )

        );
    }

    public function updateDashboardSettings(
        UpdateManagerDashboardSettingRequest $request,
        ManagerDashboardSetting $setting
    )
    {
        $setting = $this->service->updateDashboardSettings(
            $setting,
            $request->validated()
        );

        return new ManagerDashboardSettingResource(
            $setting
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Announcements
    |--------------------------------------------------------------------------
    */

    public function announcements()
    {
        return ManagerAnnouncementResource::collection(

            $this->service->announcements()

        );
    }

    public function storeAnnouncement(
        StoreManagerAnnouncementRequest $request
    )
    {
        $announcement = $this->service->createAnnouncement(
            $request->validated()
        );

        return new ManagerAnnouncementResource(
            $announcement
        );
    }

    public function updateAnnouncement(
        UpdateManagerAnnouncementRequest $request,
        ManagerAnnouncement $announcement
    )
    {
        $announcement = $this->service->updateAnnouncement(
            $announcement,
            $request->validated()
        );

        return new ManagerAnnouncementResource(
            $announcement
        );
    }

    public function destroyAnnouncement(
        ManagerAnnouncement $announcement
    ): JsonResponse
    {
        $this->service->deleteAnnouncement(
            $announcement
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.'
        ]);
    }

    
    public function reports()
    {
        return ManagerReportResource::collection(

            $this->service->reports()

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Logs
    |--------------------------------------------------------------------------
    */

    public function activityLogs()
    {
        return ManagerActivityLogResource::collection(

            $this->service->activityLogs()

        );
    }
}