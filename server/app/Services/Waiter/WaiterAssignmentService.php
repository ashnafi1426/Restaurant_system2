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
        ?string $managerId = null
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
            ->with(['waiter.user'])
            ->orderBy('priority', 'asc')
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
        ->with(['waiter.user'])
        ->orderBy('priority', 'asc')
        ->get();
        return $assignments
            ->filter(function ($assignment) {
                return $assignment->waiter && $assignment->waiter->isAvailable();
            })
            ->sortBy(function ($assignment) {
                return $assignment->waiter->current_orders;
            })
            ->pluck('waiter');
    }

    public function assignDeliveryToWaiter(
        string $floorId,
        string $shiftId,
        array $deliveryData,
        ?string $managerId = null
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
        ?string $reason = null,
        ?string $managerId = null
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

    public function deactivateWaiterAssignments(string $waiterId, ?string $reason = null): int
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
        ->with(['waiter.user'])
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

    public function getWaiterAssignments(int|string|null $waiterId = null, array $filters = [], int $perPage = 10)
    {
        $hotelId = app(\App\Services\TenantContext::class)->getHotelId();
        $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

        $query = DeliveryTask::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy']);

        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('delivery_tasks.hotel_id', $hotelId)
                  ->orWhereNull('delivery_tasks.hotel_id');
            });
        }

        if (!$isAdminOrManager && $waiterId) {
            $query->where('waiter_id', $waiterId);
        }
            
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
        return DeliveryTask::with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy'])->findOrFail($id);
    }

    public function getPendingAssignments(int|string|null $waiterId = null)
    {
        $hotelId = app(\App\Services\TenantContext::class)->getHotelId();
        $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

        $query = DeliveryTask::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereIn('status', ['assigned', 'waiting_assignment'])
            ->with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy']);

        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('delivery_tasks.hotel_id', $hotelId)
                  ->orWhereNull('delivery_tasks.hotel_id');
            });
        }

        if (!$isAdminOrManager && $waiterId) {
            $query->where('waiter_id', $waiterId);
        }

        return $query->get();
    }

    public function getActiveAssignments(int|string|null $waiterId = null)
    {
        $hotelId = app(\App\Services\TenantContext::class)->getHotelId();
        $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

        $query = DeliveryTask::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereIn('status', ['accepted', 'picked_up', 'on_delivery'])
            ->with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy']);

        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('delivery_tasks.hotel_id', $hotelId)
                  ->orWhereNull('delivery_tasks.hotel_id');
            });
        }

        if (!$isAdminOrManager && $waiterId) {
            $query->where('waiter_id', $waiterId);
        }

        return $query->get();
    }

    public function getTodayAssignments(int|string|null $waiterId = null)
    {
        $hotelId = app(\App\Services\TenantContext::class)->getHotelId();
        $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

        $query = DeliveryTask::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereDate('assigned_at', today())
            ->with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy']);

        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('delivery_tasks.hotel_id', $hotelId)
                  ->orWhereNull('delivery_tasks.hotel_id');
            });
        }

        if (!$isAdminOrManager && $waiterId) {
            $query->where('waiter_id', $waiterId);
        }

        return $query->get();
    }

    public function acceptAssignment(string $id, int|string $waiterId): DeliveryTask
    {
        \Log::info(' [SERVICE] acceptAssignment called', [
            'task_id' => $id,
            'waiter_id' => $waiterId,
        ]);
        
        $task = DeliveryTask::where('id', $id)->first() ?? DeliveryTask::where('order_id', $id)->first();
        if (!$task) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Task {$id} not found");
        }

        $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

        if ($task->waiter_id && (int) $task->waiter_id !== (int) $waiterId && !$isAdminOrManager) {
            throw new \Exception("This delivery task is assigned to another waiter");
        }

        if (!$task->waiter_id && $task->floor_id && !$isAdminOrManager) {
            $isAssigned = \App\Models\WaiterFloorAssignment::where('waiter_id', $waiterId)
                ->where('floor_id', $task->floor_id)
                ->where(function ($q) {
                    $q->where('is_active', true)
                      ->orWhere('status', 'active');
                })
                ->exists();
            if (!$isAssigned) {
                throw new \Exception("You are not assigned to the floor for this room service delivery");
            }
        }

        if ($task->waiter_id != $waiterId) {
            $task->waiter_id = $waiterId;
            $task->save();
        }
        
        $waiter = Waiter::find($waiterId);
        if ($waiter) {
            $task->accept($waiter);
        } else {
            $task->update(['status' => 'accepted', 'accepted_at' => now()]);
        }
        
        return DeliveryTask::with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy'])->find($task->id);
    }

    public function rejectAssignment(string $id, int|string $waiterId, ?string $reason): DeliveryTask
    {
        $task = DeliveryTask::where('id', $id)->first() ?? DeliveryTask::where('order_id', $id)->first();
        if (!$task) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Task {$id} not found");
        }
        $task->cancel($reason ?? 'Rejected by Staff');
        
        return DeliveryTask::with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy'])->find($task->id);
    }

    public function pickupOrder(string $id, int|string $waiterId): DeliveryTask
    {
        \Log::info(' [SERVICE] pickupOrder called', [
            'task_id' => $id,
            'waiter_id' => $waiterId,
        ]);
        
        $isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['admin', 'hotel_admin', 'manager']));

        $task = DeliveryTask::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('id', $id)->first() 
            ?? DeliveryTask::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('order_id', $id)->first();

        if (!$task) {
            $order = \App\Models\Order::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($id);
            if ($order) {
                $hotelId = app(\App\Services\TenantContext::class)->getHotelId() ?? $order->hotel_id;
                $floorId = $order->room?->getFloorId();

                if ($floorId && !$isAdminOrManager) {
                    $isAssigned = \App\Models\WaiterFloorAssignment::where('waiter_id', $waiterId)
                        ->where('floor_id', $floorId)
                        ->where(function ($q) {
                            $q->where('is_active', true)
                              ->orWhere('status', 'active');
                        })
                        ->exists();
                    if (!$isAssigned) {
                        throw new \Exception("You are not assigned to the floor for this room service delivery");
                    }
                }

                $task = DeliveryTask::create([
                    'hotel_id' => $hotelId,
                    'order_id' => $order->id,
                    'room_id' => $order->room_id,
                    'floor_id' => $floorId,
                    'waiter_id' => $waiterId,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ]);
            } else {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Task {$id} not found");
            }
        }

        if ($task->waiter_id && (int) $task->waiter_id !== (int) $waiterId && !$isAdminOrManager) {
            throw new \Exception("This delivery task is assigned to another waiter");
        }

        if ($task->waiter_id != $waiterId) {
            \Log::info("Reassigning delivery task {$id} to taking waiter {$waiterId}");
            $task->waiter_id = $waiterId;
            $task->save();
        }
        
        try {
            $task->markPickedUp();
            if ($task->order) {
                $task->order->update(['status' => 'on_delivery']);
            }
            return DeliveryTask::with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy'])->find($task->id);
        } catch (\Exception $e) {
            \Log::error(' [SERVICE] Error in pickup workflow', [
                'task_id' => $task->id,
                'status' => $task->status,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function startDelivery(string $id, int|string $waiterId): DeliveryTask
    {
        \Log::info(' [SERVICE] startDelivery called', [
            'task_id' => $id,
            'waiter_id' => $waiterId,
        ]);
        
        $task = DeliveryTask::where('id', $id)->first() ?? DeliveryTask::where('order_id', $id)->first();
        if (!$task) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Task {$id} not found");
        }

        if ($task->waiter_id != $waiterId) {
            $task->waiter_id = $waiterId;
            $task->save();
        }
        
        $task->markOnDelivery();
        if ($task->order) {
            $task->order->update(['status' => 'on_delivery']);
        }
        
        return DeliveryTask::with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy'])->find($task->id);
    }

    public function deliverOrder(string $id, int|string $waiterId, ?string $remarks): DeliveryTask
    {
        $task = DeliveryTask::where('id', $id)->first() ?? DeliveryTask::where('order_id', $id)->first();
        if (!$task) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Task {$id} not found");
        }

        if ($task->waiter_id != $waiterId) {
            $task->waiter_id = $waiterId;
            $task->save();
        }

        $task->markDelivered($remarks);
        if ($task->order) {
            $task->order->update(['status' => 'delivered']);
        }
        
        return DeliveryTask::with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy'])->find($task->id);
    }

    public function failDelivery(string $id, int|string $waiterId, string $reason, ?string $remarks): DeliveryTask
    {
        $task = DeliveryTask::where('id', $id)->first() ?? DeliveryTask::where('order_id', $id)->first();
        if (!$task) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Task {$id} not found");
        }

        if ($task->waiter_id != $waiterId) {
            $task->waiter_id = $waiterId;
            $task->save();
        }

        $task->cancel("Failed: {$reason}" . ($remarks ? " - {$remarks}" : ''));
        if ($task->order) {
            $task->order->update(['status' => 'cancelled']);
        }
        return DeliveryTask::with(['order.guest', 'order.orderItems', 'order.room', 'waiter.user', 'floor', 'assignedBy'])->find($task->id);
    }

    public function getDeliveryHistory(int|string $waiterId, array $filters = [], int $perPage = 15)
    {
        $baseQuery = DeliveryTask::whereIn('status', ['delivered', 'cancelled']);

        $waiterQuery = (clone $baseQuery)->whereIn('waiter_id', [$waiterId, auth()->id()]);
        if ($waiterQuery->exists()) {
            $query = $waiterQuery;
        } else {
            $query = $baseQuery;
        }

        $query->with([
            'room',
            'order',
            'order.guest',
            'order.room',
            'order.reservation.room',
            'assignedBy'
        ]);

        if (!empty($filters['start_date'])) {
            $query->whereRaw("COALESCE(delivered_at, assigned_at, created_at) >= ?", [$filters['start_date'] . ' 00:00:00']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereRaw("COALESCE(delivered_at, assigned_at, created_at) <= ?", [$filters['end_date'] . ' 23:59:59']);
        }

        if (!empty($filters['action'])) {
            $query->where('status', $filters['action']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $paginated = $query->paginate($perPage);

        return $paginated->through(function ($task) {
            $roomNumber = $task->room?->room_number
                ?? $task->order?->room?->room_number
                ?? $task->order?->reservation?->room?->room_number
                ?? ($task->room_id ? $task->room_id : 'N/A');

            return [
                'id' => $task->id,
                'order_id' => $task->order_id,
                'order_number' => $task->order?->order_number ?? (is_numeric($task->order_id) ? 'ORD-' . $task->order_id : substr($task->id, 0, 8)),
                'room_number' => $roomNumber,
                'guest_name' => ($task->order?->guest ? $task->order->guest->first_name . ' ' . $task->order->guest->last_name : 'N/A'),
                'status' => $task->status,
                'assigned_at' => $task->assigned_at?->format('Y-m-d H:i:s'),
                'delivered_at' => $task->delivered_at?->format('Y-m-d H:i:s'),
                'cancelled_at' => $task->cancelled_at?->format('Y-m-d H:i:s'),
                'delivery_time_minutes' => $task->getDeliveryDurationMinutes(),
                'remarks' => $task->remarks ?? 'None',
                'cancellation_reason' => $task->cancellation_reason ?? null,
                'created_at' => ($task->delivered_at ?? $task->created_at)?->format('Y-m-d H:i:s'),
            ];
        });
    }
}
