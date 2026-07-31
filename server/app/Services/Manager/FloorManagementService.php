<?php

namespace App\Services\Manager;

use App\Models\HotelFloor;
use App\Models\WaiterFloorAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class FloorManagementService
{
    public function createFloor(array $data): HotelFloor
    {
        try {
            $floor = HotelFloor::create([
                'floor_number' => $data['floor_number'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'total_rooms' => $data['total_rooms'] ?? 0,
            ]);

            Log::info("Floor created", [
                'floor_id' => $floor->id,
                'floor_number' => $floor->floor_number,
                'name' => $floor->name,
            ]);

            return $floor;
        } catch (\Exception $e) {
            Log::error("Failed to create floor: {$e->getMessage()}");
            throw $e;
        }
    }
    public function updateFloor(string $floorId, array $data): HotelFloor
    {
        try {
            $floor = HotelFloor::findOrFail($floorId);
            $floor->update(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'total_rooms' => $data['total_rooms'] ?? null,
            ], function($value) { return $value !== null; }));

            Log::info("Floor updated", ['floor_id' => $floorId]);

            return $floor;
        } catch (\Exception $e) {
            Log::error("Failed to update floor: {$e->getMessage()}");
            throw $e;
        }
    }
    public function deactivateFloor(string $floorId): bool
    {
        try {
            DB::beginTransaction();

            $floor = HotelFloor::findOrFail($floorId);
            $floor->update(['is_active' => false]);

            // Cancel all assignments for this floor
            WaiterFloorAssignment::where('floor_id', $floorId)
                ->where('status', '!=', 'completed')
                ->update(['status' => 'cancelled']);

            DB::commit();

            Log::info("Floor deactivated", ['floor_id' => $floorId]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to deactivate floor: {$e->getMessage()}");
            return false;
        }
    }
    public function activateFloor(string $floorId): bool
    {
        try {
            HotelFloor::findOrFail($floorId)->update(['is_active' => true]);

            Log::info("Floor activated", ['floor_id' => $floorId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to activate floor: {$e->getMessage()}");
            return false;
        }
    }
    public function getAllFloors()
    {
        return HotelFloor::orderBy('floor_number', 'asc')->get();
    }
    public function getActiveFloors()
    {
        return HotelFloor::active()->orderBy('floor_number', 'asc')->get();
    }
    public function getFloorWithAssignments(string $floorId)
    {
        return HotelFloor::with([
            'waiterAssignments' => function ($query) {
                $query->where('assignment_date', today());
            },
            'waiterAssignments.waiter.user',
        ])->findOrFail($floorId);
    }
    public function getFloorWorkload(string $floorId, string $date = null): int
    {
        $date = $date ?? today()->toDateString();

        return \App\Models\DeliveryTask::where('floor_id', $floorId)
            ->whereDate('assigned_at', $date)
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery', 'delivered'])
            ->count();
    }
    public function getFloorStatistics(string $floorId): array
    {
        $floor = HotelFloor::findOrFail($floorId);

        $todayAssignments = WaiterFloorAssignment::where('floor_id', $floorId)
            ->where('assignment_date', today())
            ->get();

        $todayDeliveries = \App\Models\DeliveryTask::where('floor_id', $floorId)
            ->whereDate('assigned_at', today())
            ->get();

        return [
            'floor_number' => $floor->floor_number,
            'name' => $floor->name,
            'is_active' => $floor->is_active,
            'total_rooms' => $floor->total_rooms,
            'assigned_waiters' => $todayAssignments->count(),
            'total_deliveries' => $todayDeliveries->count(),
            'completed_deliveries' => $todayDeliveries->where('status', 'delivered')->count(),
            'pending_deliveries' => $todayDeliveries->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])->count(),
            'cancelled_deliveries' => $todayDeliveries->where('status', 'cancelled')->count(),
            'average_delivery_time' => $this->calculateAverageDeliveryTime($todayDeliveries),
        ];
    }
    private function calculateAverageDeliveryTime($deliveries): float
    {
        $completedDeliveries = $deliveries->filter(function ($delivery) {
            return $delivery->status === 'delivered' && $delivery->assigned_at && $delivery->delivered_at;
        });

        if ($completedDeliveries->isEmpty()) {
            return 0;
        }

        return round($completedDeliveries->average(function ($delivery) {
            return $delivery->assigned_at->diffInMinutes($delivery->delivered_at);
        }), 2);
    }
}
