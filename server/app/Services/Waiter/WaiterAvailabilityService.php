<?php

namespace App\Services\Waiter;

use App\Models\Waiter;
use Illuminate\Support\Facades\Log;
use Throwable;

class WaiterAvailabilityService
{
    /**
     * Evaluate if a waiter is active, available, and has capacity.
     * - 'available': fully free, preferred
     * - 'busy': currently working but below max orders (still assignable)
     * - maximum_orders = 0: means unlimited capacity
     */
    public function isAvailable(Waiter $waiter): bool
    {
        try {
            if ($waiter->status !== 'active') {
                return false;
            }

            // 'offline' and 'break' are hard-blocked
            if (in_array($waiter->availability, ['offline', 'break'])) {
                return false;
            }

            // maximum_orders = 0 means unlimited
            if ($waiter->maximum_orders > 0 && $waiter->current_orders >= $waiter->maximum_orders) {
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Waiter Availability Check Exception', [
                'waiter_id' => $waiter->id,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }
}
