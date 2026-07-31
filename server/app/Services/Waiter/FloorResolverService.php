<?php

namespace App\Services\Waiter;

use App\Models\Room;
use App\Models\HotelFloor;
use Illuminate\Support\Facades\Log;
use Throwable;

class FloorResolverService
{
    /**
     * Safely determine the correct active HotelFloor from a given Room.
     */
    public function resolveForRoom(?Room $room): ?HotelFloor
    {
        if (!$room) {
            return null;
        }

        try {
            $floorId = $room->getFloorId();

            if (!$floorId) {
                Log::warning('Floor Determination Failed', [
                    'room_id' => $room->id,
                    'reason' => 'Room has no floor associated',
                ]);
                return null;
            }

            $floor = HotelFloor::where('id', $floorId)
                ->where('is_active', true)
                ->first();

            if ($floor) {
                Log::info('Floor Resolved Successfully', [
                    'room_id' => $room->id,
                    'floor_id' => $floor->id,
                ]);
                return $floor;
            }

            Log::warning('Floor Not Active or Found', [
                'room_id' => $room->id,
                'floor_id' => $floorId,
            ]);
            return null;
        } catch (Throwable $e) {
            Log::error('Floor Resolution Exception', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
