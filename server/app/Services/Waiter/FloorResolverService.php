<?php

namespace App\Services\Waiter;

use App\Models\Room;
use App\Models\Floor;
use Illuminate\Support\Facades\Log;
use Throwable;

class FloorResolverService
{
    /**
     * Resolve the active Floor model for a given hotel room.
     */
    public function resolveForRoom(?Room $room): ?Floor
    {
        if (!$room) {
            return null;
        }

        try {
            $floorId = $room->getFloorId();

            if (!$floorId) {
                Log::warning('[FloorResolver] Room has no floor associated', ['room_id' => $room->id]);
                return null;
            }

            $floor = Floor::where('id', $floorId)
                ->where('is_active', true)
                ->first();

            if ($floor) {
                Log::info('[FloorResolver] Floor resolved successfully', [
                    'room_id' => $room->id,
                    'floor_id' => $floor->id,
                    'floor_number' => $floor->floor_number,
                ]);
                return $floor;
            }

            Log::warning('[FloorResolver] Floor not found or inactive', [
                'room_id' => $room->id,
                'floor_id' => $floorId,
            ]);
            return null;
        } catch (Throwable $e) {
            Log::error('[FloorResolver] Error resolving floor for room', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
