<?php

namespace App\Listeners;

use App\Events\WaiterAssignedEvent;
use App\Events\DeliveryReassignedEvent;
use App\Models\Waiter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class UpdateWorkloadListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Handle WaiterAssignedEvent - increment workload
     *
     * @param WaiterAssignedEvent $event
     * @return void
     */
    public function handleWaiterAssigned(WaiterAssignedEvent $event): void
    {
        try {
            Log::info('UpdateWorkloadListener: Updating waiter workload (assigned)', [
                'waiter_id' => $event->waiterId,
                'delivery_id' => $event->deliveryId,
            ]);

            DB::transaction(function () use ($event) {
                // Get waiter with fresh data
                $waiter = Waiter::findOrFail($event->waiterId);

                // Increment current orders
                $waiter->increment('current_orders');

                // Check if waiter should be marked as busy
                if ($waiter->current_orders >= $waiter->max_orders) {
                    // Mark as busy
                    if ($waiter->availability_status !== 'busy') {
                        $waiter->update(['availability_status' => 'busy']);
                        
                        Log::info('Waiter marked as busy', [
                            'waiter_id' => $event->waiterId,
                            'current_orders' => $waiter->current_orders,
                            'max_orders' => $waiter->max_orders,
                        ]);
                    }
                }

                // Log workload update
                Log::info('Workload updated', [
                    'waiter_id' => $event->waiterId,
                    'previous_orders' => $waiter->current_orders - 1,
                    'current_orders' => $waiter->current_orders,
                    'max_orders' => $waiter->max_orders,
                    'availability' => $waiter->availability_status,
                ]);
            });

        } catch (Exception $e) {
            Log::error('UpdateWorkloadListener: Error updating waiter workload (assigned)', [
                'waiter_id' => $event->waiterId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle DeliveryReassignedEvent - update both waiters' workload
     *
     * @param DeliveryReassignedEvent $event
     * @return void
     */
    public function handleDeliveryReassigned(DeliveryReassignedEvent $event): void
    {
        try {
            Log::info('UpdateWorkloadListener: Updating workload (reassigned)', [
                'new_waiter_id' => $event->newWaiterId,
                'previous_waiter_id' => $event->previousWaiterId,
                'delivery_id' => $event->deliveryId,
            ]);

            DB::transaction(function () use ($event) {
                // Get waiters with fresh data
                $newWaiter = Waiter::findOrFail($event->newWaiterId);
                $previousWaiter = Waiter::findOrFail($event->previousWaiterId);

                // Decrement previous waiter's orders
                $previousWaiter->decrement('current_orders');

                // Check if previous waiter can now accept more
                if ($previousWaiter->current_orders < $previousWaiter->max_orders) {
                    if ($previousWaiter->availability_status === 'busy') {
                        $previousWaiter->update(['availability_status' => 'available']);
                        
                        Log::info('Previous waiter marked as available', [
                            'waiter_id' => $event->previousWaiterId,
                            'current_orders' => $previousWaiter->current_orders,
                            'max_orders' => $previousWaiter->max_orders,
                        ]);
                    }
                }

                // Increment new waiter's orders
                $newWaiter->increment('current_orders');

                // Check if new waiter should be marked as busy
                if ($newWaiter->current_orders >= $newWaiter->max_orders) {
                    if ($newWaiter->availability_status !== 'busy') {
                        $newWaiter->update(['availability_status' => 'busy']);
                        
                        Log::info('New waiter marked as busy', [
                            'waiter_id' => $event->newWaiterId,
                            'current_orders' => $newWaiter->current_orders,
                            'max_orders' => $newWaiter->max_orders,
                        ]);
                    }
                }

                Log::info('Workload updated for both waiters', [
                    'previous_waiter_orders' => $previousWaiter->current_orders,
                    'new_waiter_orders' => $newWaiter->current_orders,
                ]);
            });

        } catch (Exception $e) {
            Log::error('UpdateWorkloadListener: Error updating workload (reassigned)', [
                'new_waiter_id' => $event->newWaiterId,
                'previous_waiter_id' => $event->previousWaiterId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
