<?php

namespace App\Listeners;

use App\Events\OrderReadyEvent;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
class OrderReadyListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * The maximum number of exceptions to allow before bailing.
     */
    public int $maxExceptions = 2;

    /**
     * Time in seconds before the job should be retried (backoff).
     */
    public int $backoff = 10;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderReadyEvent $event): void
    {
        Log::info('🔵 [LISTENER] OrderReadyListener.handle() STARTED', [
            'order_id' => $event->order->id,
            'order_number' => $event->order->order_number,
            'timestamp' => now(),
        ]);

        try {
            // Load fresh order with all relationships
            $order = $event->order->fresh();
            
            if (!$order) {
                Log::error('Order not found when processing OrderReadyEvent', [
                    'order_id' => $event->order->id,
                ]);
                return;
            }

            Log::info('🟢 [LISTENER] Order loaded with relationships', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'room_id' => $order->room_id,
                'reservation_id' => $order->reservation_id,
                'guest_id' => $order->guest_id,
            ]);

            // Get the automatic waiter assignment service
            $assignmentService = app(AutomaticWaiterAssignmentService::class);

            // Trigger automatic waiter assignment
            Log::info('🟢 [LISTENER] Calling AutomaticWaiterAssignmentService::assignWaiterToReadyOrder', [
                'order_id' => $order->id,
            ]);

            $result = $assignmentService->assignWaiterToReadyOrder($order);

            if ($result['success']) {
                Log::info(' [LISTENER] Waiter assignment successful', [
                    'order_id' => $order->id,
                    'delivery_task_id' => $result['delivery_task_id'] ?? null,
                    'waiter_id' => $result['waiter_id'] ?? null,
                    'waiter_name' => $result['waiter_name'] ?? null,
                ]);
            } else {
                Log::warning('[LISTENER] Waiter assignment unsuccessful', [
                    'order_id' => $order->id,
                    'reason' => $result['message'] ?? 'Unknown reason',
                    'status' => $result['status'] ?? 'waiting_assignment',
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('❌ [LISTENER] Error in OrderReadyListener', [
                'order_id' => $event->order->id,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fail the job so it retries
            throw $e;
        }

        Log::info(' [LISTENER] OrderReadyListener.handle() COMPLETED', [
            'order_id' => $event->order->id,
            'timestamp' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     * Logs the terminal failure. Manager notification is already handled
     * inside AutomaticWaiterAssignmentService when no waiter can be found.
     */
    public function failed(OrderReadyEvent $event, \Throwable $exception): void
    {
        Log::error('❌ [LISTENER] OrderReadyListener Job Failed (Max Retries Exceeded)', [
            'order_id'     => $event->order->id,
            'order_number' => $event->order->order_number,
            'error_message'=> $exception->getMessage(),
            'file'         => $exception->getFile(),
            'line'         => $exception->getLine(),
            'timestamp'    => now(),
        ]);
    }
}
