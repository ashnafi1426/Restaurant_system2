<?php

namespace App\Services\Manager;

use App\Models\ManagerActivityLog;
use App\Models\ManagerAnnouncement;
use App\Models\ManagerDashboardSetting;
use App\Models\ManagerNotification;
use App\Models\ManagerReport;
use Illuminate\Support\Facades\DB;

class ManagerService
{
    public function dashboard(string $managerId): array
    {
        return [

            'unread_notifications' => ManagerNotification::where(
                'manager_id',
                $managerId
            )
            ->where('is_read', false)
            ->count(),

            'announcements' => ManagerAnnouncement::where(
                'is_active',
                true
            )->count(),

            'reports' => ManagerReport::count(),

            'activities' => ManagerActivityLog::where(
                'manager_id',
                $managerId
            )->latest()->take(10)->get(),

        ];
    }
    public function notifications()
    {
        return ManagerNotification::with('manager')
            ->latest()
            ->paginate(15);
    }
    public function createNotification(array $data)
    {
        return DB::transaction(function () use ($data) {

            return ManagerNotification::create($data);

        });
    }
    public function updateNotification(
        ManagerNotification $notification,
        array $data
    ) {
        $notification->update($data);

        return $notification->fresh();
    }
    public function deleteNotification(
        ManagerNotification $notification
    ): void
    {
        $notification->delete();
    }

    public function markAsRead(
        ManagerNotification $notification
    )
    {
        $notification->update([

            'is_read' => true,

            'read_at' => now(),

        ]);

        return $notification->fresh();
    }
    public function dashboardSettings(string $managerId)
    {
        return ManagerDashboardSetting::firstOrCreate(
            [
                'manager_id' => $managerId,
            ]
        );
    }

    public function updateDashboardSettings(
        ManagerDashboardSetting $setting,array $data)
    {
        $setting->update($data);
        return $setting->fresh();
    }
    public function announcements()
    {
        return ManagerAnnouncement::with('manager')
            ->latest()
            ->paginate(15);
    }
    public function createAnnouncement(array $data)
    {
        return DB::transaction(function () use ($data) {

            return ManagerAnnouncement::create($data);

        });
    }

    public function updateAnnouncement(
        ManagerAnnouncement $announcement,
        array $data)
    {
        $announcement->update($data);

        return $announcement->fresh();
    }

    public function deleteAnnouncement(
        ManagerAnnouncement $announcement
    ): void
    {
        $announcement->delete();
    }
    public function reports()
    {
        return ManagerReport::with('manager')
            ->latest()
            ->paginate(20);
    }

    public function activityLogs()
    {
        return ManagerActivityLog::with('manager')
            ->latest()
            ->paginate(20);
    }
    public function logActivity(array $data)
    {
        return ManagerActivityLog::create($data);
    }
}