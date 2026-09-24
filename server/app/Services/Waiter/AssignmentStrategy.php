<?php

namespace App\Services\Waiter;

use App\Models\HotelFloor;
use App\Models\HotelShift;
use App\Models\Waiter;
use App\Models\WaiterFloorAssignment;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssignmentStrategy
{
    public function __construct(
        private WaiterAvailabilityService $availabilityService
    ) {}

    public function findBestWaiter(HotelFloor $floor, HotelShift $shift): ?Waiter
    {
        try {
            $priorities = ['primary', 'secondary', 'backup'];

            foreach ($priorities as $priority) {
                $waiter = $this->findAssignedWaiter($floor->id, $shift->id, $priority);
                if ($waiter && $this->availabilityService->isAvailable($waiter)) {
                    Log::info('Waiter Selected via Floor Priority', [
                        'floor_id'  => $floor->id,
                        'priority'  => $priority,
                        'waiter_id' => $waiter->id,
                    ]);
                    return $waiter;
                }
            }

            $fallbackWaiter = Waiter::where('status', 'active')
                ->where(function ($q) {
                    $q->where('availability', 'available')
                      ->orWhere('availability', 'busy');
                })
                ->where(function ($q) {
                    $q->where('maximum_orders', 0)
                      ->orWhereRaw('current_orders < maximum_orders');
                })
                ->orderBy('current_orders', 'asc')
                ->first();

            if ($fallbackWaiter) {
                Log::info('Waiter Selected via Hotel-wide Fallback', [
                    'floor_id'    => $floor->id,
                    'waiter_id'   => $fallbackWaiter->id,
                    'availability'=> $fallbackWaiter->availability,
                    'current_orders' => $fallbackWaiter->current_orders,
                ]);
                return $fallbackWaiter;
            }

            $lastResortWaiter = Waiter::where('status', 'active')
                ->orderBy('current_orders', 'asc')
                ->first();

            if ($lastResortWaiter) {
                Log::warning('Waiter Selected via Absolute Last-Resort (may be offline/over-capacity)', [
                    'floor_id'     => $floor->id,
                    'waiter_id'    => $lastResortWaiter->id,
                    'availability' => $lastResortWaiter->availability,
                    'current_orders' => $lastResortWaiter->current_orders,
                    'maximum_orders' => $lastResortWaiter->maximum_orders,
                ]);
                return $lastResortWaiter;
            }

            $totalActive = Waiter::where('status', 'active')->count();
            Log::warning('No Waiter Available — Assignment Failed', [
                'floor_id'      => $floor->id,
                'total_active_waiters' => $totalActive,
                'reason' => $totalActive === 0
                    ? 'No waiter records exist in database'
                    : 'All waiters are inactive/suspended',
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('Assignment Strategy Exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function findAssignedWaiter(string $floorId, string $shiftId, string $priority): ?Waiter
    {
        $assignment = WaiterFloorAssignment::where([
            'floor_id' => $floorId,
            'shift_id' => $shiftId,
            'assignment_date' => now()->toDateString(),
            'priority' => $priority,
            'status' => 'active',
        ])->first();

        if ($assignment) {
            return Waiter::where('id', $assignment->waiter_id)
                         ->first();
        }

        return null;
    }
}
