<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagerAnnouncementRequest;
use App\Http\Requests\UpdateManagerAnnouncementRequest;
use App\Http\Requests\UpdateManagerDashboardSettingRequest;
use App\Http\Resources\ManagerAnnouncementResource;
use App\Http\Resources\ManagerDashboardSettingResource;
use App\Http\Resources\ManagerReportResource;
use App\Models\ManagerAnnouncement;
use App\Models\ManagerDashboardSetting;
use App\Services\Manager\ManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SettingsController extends Controller
{
    public function __construct(
        protected ManagerService $service
    ) {}

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

    public function announcements(Request $request): AnonymousResourceCollection
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

    public function reports(Request $request): AnonymousResourceCollection
    {
        return ManagerReportResource::collection(
            $this->service->reports()
        );
    }
}
