<?php

namespace App\Listeners;

use App\Events\OrderReadyEvent;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AssignWaiterListener - NOW SYNCHRONOUS (NOT QUEUED)
 * 
 * Listens for OrderReadyEvent and automatically assigns the best available waiter
 * 
 * Flow:
 * 1. Kitchen marks order as READY
 * 2. OrderReadyEvent is dispatched
 * 3. This listener catches the event IMMEDIATELY (synchronous)
 * 4. Calls AutomaticWaiterAssignmentService to perform 20-step assignment process
 * 5. System assigns delivery to waiter or marks as waiting if no waiter available
 * 
 *  CHANGED: Now runs synchronously (instantly, not queued)
 *  Waiter receives assignment within 1-2 seconds max
 *  Works WITHOUT queue:work running
 */
class AssignWaiterListener
{
    // ❌ REMOVED: implements ShouldQueue
    // ❌ REMOVED: use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        private AutomaticWaiterAssignmentService $assignmentService
    ) {}

    /**
     * Handle the event - triggered when order becomes READY
     * 
     *  NOW SYNCHRONOUS (runs immediately, no queue)
     *
     * @param OrderReadyEvent $event
     * @return void
     */
    public function handle(OrderReadyEvent $event): void
    {
        try {
            Log::info('🔵 [LISTENER] AssignWaiterListener.handle() STARTED (SYNCHRONOUS)', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'status' => $event->order->status,
                'timestamp' => now(),
            ]);

            // Call the automatic assignment service
            // This implements the complete 20-step workflow
            $result = $this->assignmentService->assignWaiterToReadyOrder($event->order);

            Log::info(' [LISTENER] AssignWaiterListener completed', [
                'order_id' => $event->order->id,
                'success' => $result['success'],
                'message' => $result['message'],
                'status' => $result['status'],
            ]);

        } catch (Throwable $e) {
            Log::error('❌ [LISTENER] AssignWaiterListener error', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log but don't throw - allows order to stay ready
            // Manager can manually assign if needed
            // (Not re-throwing because no queue retry logic anymore)
        }
    }
}
