<?php

namespace App\Services\Waiter;

use App\Models\HotelShift;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShiftResolverService
{
    /**
     * Safely determine the currently active HotelShift.
     */
    public function getCurrentShift(): ?HotelShift
    {
        try {
            $shifts = HotelShift::active()->get();

            if ($shifts->isEmpty()) {
                Log::warning('No Active Shifts Configured');
                return null;
            }

            foreach ($shifts as $shift) {
                if ($shift->isCurrentShift()) {
                    Log::info('Current Shift Resolved', [
                        'shift_id' => $shift->id,
                        'shift_name' => $shift->name,
                    ]);
                    return $shift;
                }
            }

            Log::warning('No Shift Matches Current Time', [
                'current_time' => now()->format('H:i'),
            ]);
            return null;

        } catch (Throwable $e) {
            Log::error('Shift Resolution Exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
