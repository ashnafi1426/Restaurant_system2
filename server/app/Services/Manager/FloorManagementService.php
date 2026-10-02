<?php

namespace App\Services\Manager;

use App\Models\Floor;
use App\Models\Room;
use App\Models\DeliveryTask;
use App\Models\WaiterFloorAssignment;
use App\Services\TenantContext;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class FloorManagementService
{
    /**
     * Get paginated list of floors for current tenant with room counts and filters.
     */
    public function listFloors(array $filters = [], int $perPage = 100): LengthAwarePaginator
    {
        $hotelId = app(TenantContext::class)->getHotelId()
            ?: request()->input('hotel_id')
            ?: request()->header('X-Hotel-ID')
            ?: request()->header('x-hotel-id')
            ?: auth()->user()?->hotel_id;

        if ($hotelId) {
            $this->ensureFloorsExistForHotel($hotelId);
        }

        $query = Floor::query()->withCount('rooms');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('floor_number', (int) $search);
                }
            });
        }

        return $query->orderBy('floor_number')->paginate($perPage);
    }

    /**
     * Auto-discover or backfill floors from existing rooms if hotel has none.
     */
    public function ensureFloorsExistForHotel(string $hotelId): void
    {
        $floorsCount = Floor::withoutTenant()->where('hotel_id', $hotelId)->count();
        if ($floorsCount > 0) {
            return;
        }

        // 1. Backfill from distinct room floor numbers
        $existingRoomFloors = Room::withoutTenant()
            ->where('hotel_id', $hotelId)
            ->whereNotNull('floor')
            ->distinct()
            ->pluck('floor');

        foreach ($existingRoomFloors as $flNum) {
            $num = (int) $flNum;
            if ($num > 0) {
                $created = Floor::withoutTenant()->firstOrCreate(
                    ['hotel_id' => $hotelId, 'floor_number' => $num],
                    [
                        'id' => (string) Str::uuid(),
                        'name' => "Floor {$num}",
                        'description' => "Floor {$num}",
                        'is_active' => true,
                    ]
                );
                Room::withoutTenant()
                    ->where('hotel_id', $hotelId)
                    ->where('floor', $num)
                    ->whereNull('floor_id')
                    ->update(['floor_id' => $created->id]);
            }
        }

        // 2. Default floor 1 if still empty
        if (Floor::withoutTenant()->where('hotel_id', $hotelId)->count() === 0) {
            Floor::withoutTenant()->firstOrCreate(
                ['hotel_id' => $hotelId, 'floor_number' => 1],
                [
                    'id' => (string) Str::uuid(),
                    'name' => 'Floor 1',
                    'description' => 'First Floor',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Create a new floor.
     */
    public function createFloor(array $data): Floor
    {
        $hotelId = app(TenantContext::class)->getHotelId();

        $floor = Floor::create([
            'id' => (string) Str::uuid(),
            'hotel_id' => $hotelId,
            'floor_number' => (int) $data['floor_number'],
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'is_active' => $data['is_active'] ?? true,
            'total_rooms' => (int) ($data['total_rooms'] ?? 0),
        ]);

        Log::info('[FloorManagementService] Floor created', [
            'floor_id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'hotel_id' => $hotelId,
        ]);

        return $floor;
    }

    /**
     * Update an existing floor.
     */
    public function updateFloor(Floor $floor, array $data): Floor
    {
        $payload = [];
        if (array_key_exists('floor_number', $data)) {
            $payload['floor_number'] = (int) $data['floor_number'];
        }
        if (array_key_exists('name', $data)) {
            $payload['name'] = trim($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'] !== null ? trim($data['description']) : null;
        }
        if (array_key_exists('total_rooms', $data)) {
            $payload['total_rooms'] = (int) $data['total_rooms'];
        }
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        $floor->update($payload);

        Log::info('[FloorManagementService] Floor updated', ['floor_id' => $floor->id]);

        return $floor;
    }

    /**
     * Delete a floor after verifying no active blockers exist.
     */
    public function deleteFloor(Floor $floor): void
    {
        // 1. Check for active waiter assignments
        $activeAssignments = $floor->waiterAssignments()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhere('status', 'active');
            })
            ->count();

        if ($activeAssignments > 0) {
            throw ValidationException::withMessages([
                'floor' => 'Cannot delete floor with active waiter assignments. Please remove assignments first.',
            ]);
        }

        // 2. Check for assigned rooms
        $roomCount = $floor->rooms()->count();
        if ($roomCount > 0) {
            throw ValidationException::withMessages([
                'floor' => "Cannot delete floor containing {$roomCount} assigned room(s). Please reassign or delete the rooms first.",
            ]);
        }

        $floor->delete();

        Log::info('[FloorManagementService] Floor deleted', ['floor_id' => $floor->id]);
    }

    /**
     * Deactivate floor and cancel pending assignments.
     */
    public function deactivateFloor(Floor $floor): Floor
    {
        DB::transaction(function () use ($floor) {
            $floor->update(['is_active' => false]);

            WaiterFloorAssignment::where('floor_id', $floor->id)
                ->where('status', '!=', 'completed')
                ->update(['status' => 'cancelled', 'is_active' => false]);
        });

        Log::info('[FloorManagementService] Floor deactivated', ['floor_id' => $floor->id]);

        return $floor->fresh();
    }

    /**
     * Activate a floor.
     */
    public function activateFloor(Floor $floor): Floor
    {
        $floor->update(['is_active' => true]);

        Log::info('[FloorManagementService] Floor activated', ['floor_id' => $floor->id]);

        return $floor->fresh();
    }

    /**
     * Get comprehensive statistics for a floor.
     */
    public function getFloorStats(Floor $floor): array
    {
        $today = today();
        $floorId = $floor->id;

        $totalRooms = $floor->rooms()->count();
        $occupiedRooms = $floor->rooms()->where('status', 'occupied')->count();
        $availableRooms = $floor->rooms()->where('status', 'available')->count();

        $todayAssignments = WaiterFloorAssignment::where('floor_id', $floorId)
            ->where('assignment_date', $today)
            ->get();

        $activeWaitersCount = $todayAssignments
            ->where('status', 'active')
            ->pluck('waiter_id')
            ->unique()
            ->count();

        $todayDeliveries = DeliveryTask::where('floor_id', $floorId)
            ->whereDate('assigned_at', $today)
            ->get();

        $completedDeliveries = $todayDeliveries->where('status', 'delivered');
        $averageDeliveryTime = 0.0;
        if ($completedDeliveries->isNotEmpty()) {
            $times = $completedDeliveries->filter(fn($d) => $d->assigned_at && $d->delivered_at)
                ->map(fn($d) => $d->assigned_at->diffInMinutes($d->delivered_at));
            $averageDeliveryTime = $times->isNotEmpty() ? round($times->average(), 1) : 0.0;
        }

        return [
            'floor_id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'name' => $floor->name,
            'is_active' => (bool) $floor->is_active,
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'available_rooms' => $availableRooms,
            'total_assignments' => $todayAssignments->count(),
            'active_waiters' => $activeWaitersCount,
            'assigned_waiters' => $todayAssignments->count(),
            'total_deliveries' => $todayDeliveries->count(),
            'completed_deliveries' => $completedDeliveries->count(),
            'pending_deliveries' => $todayDeliveries->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])->count(),
            'cancelled_deliveries' => $todayDeliveries->where('status', 'cancelled')->count(),
            'average_delivery_time' => $averageDeliveryTime,
        ];
    }
}
