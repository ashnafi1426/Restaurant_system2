<?php

namespace App\Listeners;

use App\Events\OrderReadyEvent;
use App\Services\Waiter\AutomaticWaiterAssignmentService;
use App\Models\WaiterTableAssignment;
use App\Models\HotelShift;
use App\Models\DeliveryTask;
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
                'order_type' => $order->order_type,
                'room_id' => $order->room_id,
                'table_id' => $order->table_id,
                'reservation_id' => $order->reservation_id,
                'guest_id' => $order->guest_id,
            ]);

            // Check if this is a walk-in order (table-based)
            if ($order->order_type === 'walk_in' && $order->table_id) {
                Log::info('🟡 [LISTENER] Walk-in order detected - checking table assignment', [
                    'order_id' => $order->id,
                    'table_id' => $order->table_id,
                ]);

                // Get current shift based on time
                $currentShift = HotelShift::getCurrentShift();
                
                if ($currentShift) {
                    Log::info('🟡 [LISTENER] Current shift found', [
                        'shift_id' => $currentShift->id,
                        'shift_name' => $currentShift->name,
                    ]);

                    // Get assigned waiter for this table
                    $assignment = WaiterTableAssignment::getAssignedWaiter(
                        $order->table_id,
                        $currentShift->id,
                        today()
                    );

                    if ($assignment && $assignment->waiter) {
                        Log::info('✅ [LISTENER] Found waiter assigned to table', [
                            'assignment_id' => $assignment->id,
                            'waiter_id' => $assignment->waiter_id,
                            'waiter_name' => $assignment->waiter->user->name ?? 'Unknown',
                            'priority' => $assignment->priority,
                        ]);

                        // Create delivery task directly
                        $deliveryTask = DeliveryTask::create([
                            'order_id' => $order->id,
                            'waiter_id' => $assignment->waiter_id,
                            'table_id' => $order->table_id,
                            'status' => 'pending',
                            'assigned_at' => now(),
                        ]);

                        Log::info('✅ [LISTENER] Delivery task created for walk-in order', [
                            'delivery_task_id' => $deliveryTask->id,
                            'waiter_id' => $assignment->waiter_id,
                            'table_id' => $order->table_id,
                        ]);

                        // TODO: Send notification to waiter
                        // event(new WaiterNotificationEvent($assignment->waiter_id, ...));

                        return; // Success - waiter assigned based on table assignment
                    } else {
                        Log::warning('⚠️ [LISTENER] No waiter assigned to this table', [
                            'table_id' => $order->table_id,
                            'shift_id' => $currentShift->id,
                            'date' => today()->toDateString(),
                        ]);
                        // Fall through to automatic assignment
                    }
                } else {
                    Log::warning('⚠️ [LISTENER] No active shift found at current time');
                    // Fall through to automatic assignment
                }
            }

            // For room service orders OR walk-in orders without table assignment,
            // use the automatic waiter assignment service
            Log::info('🟢 [LISTENER] Using automatic waiter assignment', [
                'order_id' => $order->id,
                'order_type' => $order->order_type,
            ]);

            $assignmentService = app(AutomaticWaiterAssignmentService::class);
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
