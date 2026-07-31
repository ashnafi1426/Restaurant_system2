<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerService;
use App\Http\Requests\UpdateManagerDashboardSettingRequest;
use App\Http\Requests\StoreManagerAnnouncementRequest;
use App\Http\Requests\UpdateManagerAnnouncementRequest;
use App\Http\Resources\ManagerDashboardSettingResource;
use App\Http\Resources\ManagerAnnouncementResource;
use App\Http\Resources\ManagerReportResource;
use App\Models\ManagerDashboardSetting;
use App\Models\ManagerAnnouncement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager Settings Controller
 * 
 * Handles settings, announcements, and reports
 */
class SettingsController extends Controller
{
    protected ManagerService $service;

    public function __construct(ManagerService $service)
    {
        $this->service = $service;
    }

    /**
     * Get dashboard settings
     */
    public function dashboardSettings(Request $request)
    {
        return new ManagerDashboardSettingResource(
            $this->service->dashboardSettings($request->user()->id)
        );
    }

    /**
     * Update dashboard settings
     */
    public function updateDashboardSettings(
        UpdateManagerDashboardSettingRequest $request,
        ManagerDashboardSetting $setting
    )
    {
        $setting = $this->service->updateDashboardSettings(
            $setting,
            $request->validated()
        );

        return new ManagerDashboardSettingResource($setting);
    }

    /**
     * Get all announcements
     */
    public function announcements(Request $request)
    {
        return ManagerAnnouncementResource::collection(
            $this->service->announcements()
        );
    }

    /**
     * Create announcement
     */
    public function storeAnnouncement(StoreManagerAnnouncementRequest $request)
    {
        $announcement = $this->service->createAnnouncement(
            $request->validated()
        );

        return new ManagerAnnouncementResource($announcement);
    }

    /**
     * Update announcement
     */
    public function updateAnnouncement(
        UpdateManagerAnnouncementRequest $request,
        ManagerAnnouncement $announcement
    )
    {
        $announcement = $this->service->updateAnnouncement(
            $announcement,
            $request->validated()
        );

        return new ManagerAnnouncementResource($announcement);
    }

    /**
     * Delete announcement
     */
    public function destroyAnnouncement(
        ManagerAnnouncement $announcement
    ): JsonResponse
    {
        $this->service->deleteAnnouncement($announcement);

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.'
        ]);
    }

    /**
     * Get all reports
     */
    public function reports(Request $request)
    {
        return ManagerReportResource::collection(
            $this->service->reports()
        );
    }
}
