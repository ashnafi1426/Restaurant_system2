<?php

namespace App\Services\Waiter;

use App\Models\Waiter;
use Illuminate\Support\Facades\Log;
use Throwable;

class WaiterAvailabilityService
{
    public function isAvailable(Waiter $waiter): bool
    {
        try {
            if ($waiter->status !== 'active') {
                return false;
            }

            if (in_array($waiter->availability, ['offline', 'break'])) {
                return false;
            }

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
