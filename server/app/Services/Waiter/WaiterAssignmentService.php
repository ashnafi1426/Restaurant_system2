<?php

namespace App\Services\Waiter;

use App\Models\Waiter;
use App\Models\HotelFloor;
use App\Models\HotelShift;
use App\Models\WaiterFloorAssignment;
use App\Models\DeliveryTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class WaiterAssignmentService
{
    public function assignWaiterToFloor(
        string $waiterId,
        string $floorId,
        string $shiftId,
        string $priority = 'primary',
        string $managerId = null
    ): WaiterFloorAssignment {
        try {
            DB::beginTransaction();
            $waiter = Waiter::findOrFail($waiterId);
            if ($waiter->status !== 'active') {
                throw new \Exception("Cannot assign inactive waiter: {$waiter->user->name}");
            }
            $floor = HotelFloor::findOrFail($floorId);
            $shift = HotelShift::findOrFail($shiftId);
            if ($priority === 'primary') {
                $existing = WaiterFloorAssignment::where([
                    ['floor_id', '=', $floorId],
                    ['shift_id', '=', $shiftId],
                    ['assignment_date', '=', now()->toDateString()],
                    ['priority', '=', 'primary'],
                    ['status', '!=', 'cancelled'],
                ])->exists();

                if ($existing) {
                    throw new \Exception("Primary waiter already assigned for this floor/shift");
                }
            }

            // Create assignment
            $assignment = WaiterFloorAssignment::create([
                'waiter_id' => $waiterId,
                'floor_id' => $floorId,
                'shift_id' => $shiftId,
                'assignment_date' => now()->toDateString(),
                'status' => 'active',
                'priority' => $priority,
                'assigned_by' => $managerId,
            ]);

            DB::commit();

            Log::info("Waiter assigned to floor", [
                'waiter_id' => $waiterId,
                'waiter_name' => $waiter->user->name,
                'floor_id' => $floorId,
                'floor_number' => $floor->floor_number,
                'priority' => $priority,
            ]);

            return $assignment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to assign waiter to floor: {$e->getMessage()}");
            throw $e;
        }
    }
    public function findBestAvailableWaiter(string $floorId, string $shiftId): ?Waiter
    {
        try {
            $floor = HotelFloor::findOrFail($floorId);
            $shift = HotelShift::findOrFail($shiftId);
            $assignments = WaiterFloorAssignment::where([
                ['floor_id', '=', $floorId],
                ['shift_id', '=', $shiftId],
                ['assignment_date', '=', now()->toDateString()],
                ['status', '=', 'active'],
            ])
            ->orderBy('priority', 'asc') // Primary first
            ->get();

            foreach ($assignments as $assignment) {
                $waiter = $assignment->waiter;
                if (!$this->isWaiterInShift($waiter, $shift)) {
                    continue;
                }
                if (!$waiter->isAvailable()) {
                    continue;
                }
                if ($waiter->current_orders >= $waiter->maximum_orders) {
                    continue;
                }

                return $waiter;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error finding available waiter: {$e->getMessage()}");
            return null;
        }
    }
    public function getAvailableWaiters(string $floorId, string $shiftId): Collection
    {
        $assignments = WaiterFloorAssignment::where([
            ['floor_id', '=', $floorId],
            ['shift_id', '=', $shiftId],
            ['assignment_date', '=', now()->toDateString()],
            ['status', '=', 'active'],
        ])
        ->with('waiter')
        ->orderBy('priority', 'asc') // Primary first
        ->get();
        return $assignments
            ->filter(function ($assignment) {
                return $assignment->waiter && $assignment->waiter->isAvailable();
            })
            ->sortBy(function ($assignment) {
                return $assignment->waiter->current_orders; // Lowest workload first
            })
            ->pluck('waiter');
    }
    public function assignDeliveryToWaiter(
        string $floorId,
        string $shiftId,
        array $deliveryData,
        string $managerId = null
    ): ?DeliveryTask {
        try {
            DB::beginTransaction();

            $waiter = $this->findBestAvailableWaiter($floorId, $shiftId);

            if (!$waiter) {
                Log::warning("No available waiter for delivery", [
                    'floor_id' => $floorId,
                    'shift_id' => $shiftId,
                ]);
                DB::rollBack();
                return null;
            }

            $task = DeliveryTask::create([
                ...$deliveryData,
                'floor_id' => $floorId,
                'waiter_id' => $waiter->id,
                'assigned_by' => $managerId,
                'assignment_type' => 'automatic',
                'status' => 'accepted',
                'assigned_at' => now(),
                'accepted_at' => now(),
            ]);

            $waiter->incrementOrders();

            DB::commit();

            Log::info("Delivery assigned to waiter", [
                'delivery_id' => $task->id,
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->user->name,
            ]);

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to assign delivery: {$e->getMessage()}");
            return null;
        }
    }
    public function reassignDelivery(
        string $deliveryTaskId,
        string $newWaiterId,
        string $reason = null,
        string $managerId = null
    ): bool {
        try {
            DB::beginTransaction();

            $task = DeliveryTask::findOrFail($deliveryTaskId);
            $newWaiter = Waiter::findOrFail($newWaiterId);

            if (!$newWaiter->isAvailable()) {
                throw new \Exception("Target waiter is not available");
            }

            $task->reassign($newWaiter, $managerId, $reason);

            DB::commit();

            Log::info("Delivery reassigned", [
                'delivery_id' => $deliveryTaskId,
                'old_waiter' => $task->waiter_id,
                'new_waiter' => $newWaiterId,
                'reason' => $reason,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to reassign delivery: {$e->getMessage()}");
            return false;
        }
    }
    private function isWaiterInShift(Waiter $waiter, HotelShift $shift): bool
    {
        $now = now();
        $shiftStart = $now->copy()->setTimeFromTimeString($shift->start_time);
        $shiftEnd = $now->copy()->setTimeFromTimeString($shift->end_time);

        // Handle night shift that crosses midnight
        if ($shiftEnd < $shiftStart) {
            $shiftEnd = $shiftEnd->addDay();
        }

        return $now >= $shiftStart && $now <= $shiftEnd;
    }
    public function getWaiterWorkload(string $waiterId): int
    {
        return DeliveryTask::where('waiter_id', $waiterId)
            ->whereDate('assigned_at', today())
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
            ->count();
    }
    public function deactivateWaiterAssignments(string $waiterId, string $reason = null): int
    {
        return WaiterFloorAssignment::where('waiter_id', $waiterId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->update(['status' => 'cancelled']);
    }
    public function getAssignmentsSummary(string $floorId, string $shiftId): array
    {
        $assignments = WaiterFloorAssignment::where([
            ['floor_id', '=', $floorId],
            ['shift_id', '=', $shiftId],
            ['assignment_date', '=', now()->toDateString()],
        ])
        ->with('waiter')
        ->get();

        return [
            'total' => $assignments->count(),
            'primary' => $assignments->where('priority', 'primary')->count(),
            'secondary' => $assignments->where('priority', 'secondary')->count(),
            'backup' => $assignments->where('priority', 'backup')->count(),
            'active' => $assignments->where('status', 'active')->count(),
            'assignments' => $assignments,
        ];
    }
    public function getWaiterAssignments(int|string $waiterId, array $filters, int $perPage)
    {
        $query = DeliveryTask::where('waiter_id', $waiterId)
            ->with(['order', 'order.guest', 'floor', 'assignedBy']);
            
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['date'])) {
            $query->whereDate('assigned_at', $filters['date']);
        }
        
        $sortBy = $filters['sort_by'] ?? 'assigned_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        return $query->paginate($perPage);
    }

    public function getAssignment(string $id): DeliveryTask
    {
        return DeliveryTask::with(['order', 'order.guest', 'floor', 'assignedBy'])->findOrFail($id);
    }

    public function getPendingAssignments(int|string $waiterId)
    {
        return DeliveryTask::where('waiter_id', $waiterId)
            ->where('status', 'assigned')
            ->with(['order', 'order.guest', 'floor'])
            ->get();
    }

    public function getActiveAssignments(int|string $waiterId)
    {
        return DeliveryTask::where('waiter_id', $waiterId)
            ->whereIn('status', ['accepted', 'picked_up', 'on_delivery'])
            ->with(['order', 'order.guest', 'floor'])
            ->get();
    }

    public function getTodayAssignments(int|string $waiterId)
    {
        return DeliveryTask::where('waiter_id', $waiterId)
            ->whereDate('assigned_at', today())
            ->with(['order', 'order.guest', 'floor'])
            ->get();
    }

    public function acceptAssignment(string $id, int|string $waiterId): DeliveryTask
    {
        \Log::info('🔵 [SERVICE] acceptAssignment called', [
            'task_id' => $id,
            'waiter_id' => $waiterId,
        ]);
        
        $task = DeliveryTask::where('id', $id)->where('waiter_id', $waiterId)->firstOrFail();
        
        \Log::info('📦 [SERVICE] Task found before accept', [
            'task_id' => $task->id,
            'status_before' => $task->status,
            'waiter_id' => $task->waiter_id,
        ]);
        
        $waiter = Waiter::findOrFail($waiterId);
        $task->accept($waiter);
        
        \Log::info('✅ [SERVICE] Task accepted', [
            'task_id' => $task->id,
            'status_after' => $task->status,
            'accepted_at' => $task->accepted_at,
        ]);
        
        // Re-fetch fresh from database to ensure response has updated status
        $task = DeliveryTask::with(['order', 'order.guest', 'floor', 'assignedBy'])->find($id);
        
        return $task;
    }

    public function rejectAssignment(string $id, int|string $waiterId, ?string $reason): DeliveryTask
    {
        $task = DeliveryTask::where('id', $id)->where('waiter_id', $waiterId)->firstOrFail();
        $task->cancel($reason ?? 'Rejected by Waiter');
        
        // Re-fetch fresh from database to ensure response has updated status
        $task = DeliveryTask::with(['order', 'order.guest', 'floor', 'assignedBy'])->find($id);
        
        return $task;
    }

    public function pickupOrder(string $id, int|string $waiterId): DeliveryTask
    {
        \Log::info('🔵 [SERVICE] pickupOrder called', [
            'task_id' => $id,
            'waiter_id' => $waiterId,
        ]);
        
        $task = DeliveryTask::where('id', $id)->where('waiter_id', $waiterId)->firstOrFail();
        
        \Log::info('📦 [SERVICE] Task found before markPickedUp', [
            'task_id' => $task->id,
            'status_before' => $task->status,
            'waiter_id' => $task->waiter_id,
            'order_id' => $task->order_id,
        ]);
        
        try {
            // Mark task as picked up from kitchen
            // Do NOT auto-transition to on_delivery - let the waiter explicitly call startDelivery
            $task->markPickedUp();
            
            \Log::info('✅ [SERVICE] Task marked as picked up from kitchen', [
                'task_id' => $task->id,
                'new_status' => $task->status,
                'picked_up_at' => $task->picked_up_at,
            ]);
            
            // Re-fetch fresh from database to ensure response has updated status
            $task = DeliveryTask::with(['order', 'order.guest', 'floor', 'assignedBy'])->find($id);
            
        } catch (\Exception $e) {
            \Log::error('❌ [SERVICE] Error in pickup workflow', [
                'task_id' => $task->id,
                'status' => $task->status,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
        
        return $task;
    }

    public function startDelivery(string $id, int|string $waiterId): DeliveryTask
    {
        \Log::info('🔵 [SERVICE] startDelivery called', [
            'task_id' => $id,
            'waiter_id' => $waiterId,
        ]);
        
        $task = DeliveryTask::where('id', $id)->where('waiter_id', $waiterId)->firstOrFail();
        
        \Log::info('📦 [SERVICE] Task found before markOnDelivery', [
            'task_id' => $task->id,
            'status_before' => $task->status,
            'waiter_id' => $task->waiter_id,
            'order_id' => $task->order_id,
        ]);
        
        $task->markOnDelivery();
        
        // Re-fetch fresh from database to ensure response has updated status
        $task = DeliveryTask::with(['order', 'order.guest', 'floor', 'assignedBy'])->find($id);
        
        \Log::info('✅ [SERVICE] Task updated to on_delivery', [
            'task_id' => $task->id,
            'status_after' => $task->status,
            'on_delivery_at' => $task->on_delivery_at,
        ]);
        
        return $task;
    }

    public function deliverOrder(string $id, int|string $waiterId, ?string $remarks): DeliveryTask
    {
        $task = DeliveryTask::where('id', $id)->where('waiter_id', $waiterId)->firstOrFail();
        $task->markDelivered($remarks);
        
        // Re-fetch fresh from database to ensure response has updated status
        $task = DeliveryTask::with(['order', 'order.guest', 'floor', 'assignedBy'])->find($id);
        
        return $task;
    }

    public function failDelivery(string $id, int|string $waiterId, string $reason, ?string $remarks): DeliveryTask
    {
        $task = DeliveryTask::where('id', $id)->where('waiter_id', $waiterId)->firstOrFail();
        $task->cancel("Failed: {$reason}" . ($remarks ? " - {$remarks}" : ''));
        return $task;
    }

    /**
     * Get delivery history with filters and pagination
     * Returns all completed/cancelled deliveries for a waiter
     */
    public function getDeliveryHistory(int|string $waiterId, array $filters = [], int $perPage = 15)
    {
        $query = DeliveryTask::where('waiter_id', $waiterId)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with([
                'order:id,order_number,room_id,guest_id,status',
                'order.guest:id,first_name,last_name',
                'order.room:id,room_number',
                'assignedBy:id,first_name,last_name'
            ]);

        // Apply date range filter
        if (!empty($filters['start_date'])) {
            $query->whereDate('assigned_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('assigned_at', '<=', $filters['end_date']);
        }

        // Apply action filter (status filter)
        if (!empty($filters['action'])) {
            $query->where('status', $filters['action']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Map results to include necessary fields for frontend
        $paginated = $query->paginate($perPage);

        return $paginated->through(function ($task) {
            return [
                'id' => $task->id,
                'order_id' => $task->order_id,
                'order_number' => $task->order?->order_number,
                'room_number' => $task->order?->room?->room_number,
                'guest_name' => ($task->order?->guest ? $task->order->guest->first_name . ' ' . $task->order->guest->last_name : 'N/A'),
                'status' => $task->status,
                'assigned_at' => $task->assigned_at?->format('Y-m-d H:i:s'),
                'delivered_at' => $task->delivered_at?->format('Y-m-d H:i:s'),
                'cancelled_at' => $task->cancelled_at?->format('Y-m-d H:i:s'),
                'delivery_time_minutes' => $task->getDeliveryDurationMinutes(),
                'remarks' => $task->remarks ?? 'None',
                'cancellation_reason' => $task->cancellation_reason ?? null,
                'created_at' => $task->created_at?->format('Y-m-d H:i:s'),
            ];
        });
    }
}
