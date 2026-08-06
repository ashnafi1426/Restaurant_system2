<?php

namespace App\Listeners;

use App\Events\OrderReadyEvent;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use Illuminate\Support\Facades\Log;
use Throwable;
class AssignWaiterListener
{
    public function __construct(
        private AutomaticWaiterAssignmentService $assignmentService
    ) {}

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
