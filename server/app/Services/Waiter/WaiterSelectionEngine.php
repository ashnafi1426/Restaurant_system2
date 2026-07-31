<?php

namespace App\Services\Waiter;

use App\Models\Waiter;
use App\Models\HotelFloor;
use App\Models\HotelShift;
use App\Models\WaiterFloorAssignment;
use Illuminate\Support\Facades\Log;
use Throwable;
class WaiterSelectionEngine
{
    public function selectBestWaiter(HotelFloor $floor, HotelShift $shift): ?Waiter
    {
        Log::info('🔵 [SELECTION ENGINE] Starting waiter selection', [
            'floor_id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'shift_id' => $shift->id,
            'shift_name' => $shift->name,
            'timestamp' => now(),
        ]);
        $waiter = $this->selectFromFloorStaff($floor, $shift);
        
        if ($waiter) {
            Log::info(' [SELECTION] Waiter selected from floor staff', [
                'waiter_id' => $waiter->id,
                'name' => $waiter->user->name,
                'tier' => 'floor_assignment'
            ]);
            return $waiter;
        }
        $waiter = $this->selectFromHotelStaff($floor, $shift);
        
        if ($waiter) {
            Log::info(' [SELECTION] Waiter selected from hotel staff', [
                'waiter_id' => $waiter->id,
                'name' => $waiter->user->name,
                'tier' => 'hotel_staff',
                'original_floor' => $waiter->floorAssignments()->first()?->floor->floor_number ?? 'unassigned'
            ]);
            return $waiter;
        }
        Log::warning(' [SELECTION] NO WAITER AVAILABLE', [
            'floor_id' => $floor->id,
            'shift_id' => $shift->id,
            'timestamp' => now(),
            'action' => 'Will create waiting_assignment task'
        ]);
        
        return null;  // Caller will create waiting task
    }
    private function selectFromFloorStaff(HotelFloor $floor, HotelShift $shift): ?Waiter
    {
        Log::info('🔍 [TIER 1-3] Starting floor staff selection (Primary → Secondary → Backup)', [
            'floor_id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'shift_id' => $shift->id,
            'shift_name' => $shift->name ?? 'Unknown',
            'date' => today()->toDateString(),
            'timestamp' => now(),
        ]);

        // Query: Get all waiters assigned to this floor today, ordered by priority
        try {
            $assignments = WaiterFloorAssignment::where([
                ['floor_id' => $floor->id],
                ['shift_id' => $shift->id],
                ['assignment_date' => today()->toDateString()],
                ['status' => 'active'],
            ])
            ->orderBy('priority', 'asc')  // 'primary' (0) → 'secondary' (1) → 'backup' (2)
            ->with(['waiter', 'waiter.user'])
            ->get();

            Log::debug('[TIER 1-3] Floor assignment records retrieved', [
                'floor_id' => $floor->id,
                'assignment_count' => $assignments->count(),
                'date' => today()->toDateString(),
            ]);

            if ($assignments->isEmpty()) {
                Log::warning(' [TIER 1-3] No floor assignments found - floor may not be staffed', [
                    'floor_id' => $floor->id,
                    'floor_number' => $floor->floor_number,
                    'date' => today()->toDateString(),
                    'shift_id' => $shift->id,
                    'action' => 'Will fallback to TIER 7 hotel-wide search',
                ]);
                return null;
            }

        } catch (Throwable $e) {
            Log::error('❌ [TIER 1-3] Error fetching floor assignments', [
                'error' => $e->getMessage(),
                'floor_id' => $floor->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }

        // Iterate through assignments in priority order: PRIMARY → SECONDARY → BACKUP
        $tierNumber = 1;
        foreach ($assignments as $assignment) {
            $waiter = $assignment->waiter;
            $priority = $assignment->priority;
            $waiterName = $waiter?->user?->name ?? 'Unknown';

            // Determine tier number for logging
            $tierLabel = match($priority) {
                'primary' => 'TIER 1 (PRIMARY)',
                'secondary' => 'TIER 2 (SECONDARY)',
                'backup' => 'TIER 3 (BACKUP)',
                default => "TIER {$tierNumber}",
            };

            Log::info("🔵 [{$tierLabel}] Evaluating {$priority} waiter", [
                'waiter_id' => $waiter->id ?? 'NULL',
                'name' => $waiterName,
                'priority' => $priority,
                'floor_id' => $floor->id,
                'floor_number' => $floor->floor_number,
            ]);

            if (!$waiter) {
                Log::warning("[TIER] Waiter record not found for assignment", [
                    'assignment_id' => $assignment->id,
                    'priority' => $priority,
                ]);
                continue;  // Skip to next assignment
            }

            // Log detailed waiter state
            Log::debug("[{$tierLabel}] Waiter state details", [
                'waiter_id' => $waiter->id,
                'name' => $waiterName,
                'status' => $waiter->status,
                'availability' => $waiter->availability,
                'current_orders' => $waiter->current_orders,
                'maximum_orders' => $waiter->maximum_orders,
                'capacity_remaining' => $waiter->maximum_orders - $waiter->current_orders,
                'utilization_percent' => round(($waiter->current_orders / $waiter->maximum_orders) * 100, 2),
            ]);

            // VALIDATION: Check if this waiter is eligible
            if ($this->isWaiterEligible($waiter, $shift, $priority)) {
                Log::info(" [{$tierLabel}] Eligible waiter found - ASSIGNMENT WILL PROCEED", [
                    'waiter_id' => $waiter->id,
                    'name' => $waiterName,
                    'priority' => $priority,
                    'current_orders' => $waiter->current_orders,
                    'remaining_capacity' => $waiter->maximum_orders - $waiter->current_orders,
                    'message' => "Order from {$floor->floor_number} will be assigned to {$waiterName}",
                ]);
                return $waiter;  //  Found eligible waiter - return immediately
            }

            // Log why waiter was rejected (for debugging)
            $reason = $this->getIneligibilityReason($waiter, $shift);
            Log::warning("❌ [{$tierLabel}] Waiter rejected - {$priority} unavailable", [
                'waiter_id' => $waiter->id,
                'name' => $waiterName,
                'reason' => $reason,
                'status' => $waiter->status,
                'availability' => $waiter->availability,
                'workload' => "{$waiter->current_orders}/{$waiter->maximum_orders}",
                'next_check' => $priority === 'primary' ? 'Will try SECONDARY' : 
                               ($priority === 'secondary' ? 'Will try BACKUP' : 
                               'Will search TIER 7 (entire hotel)'),
            ]);

            $tierNumber++;
        }

        // No eligible waiter found in floor staff (all primary/secondary/backup unavailable)
        Log::warning('❌ [TIER 1-3] All floor staff unavailable', [
            'floor_id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'assignments_checked' => $assignments->count(),
            'all_status' => 'Primary/Secondary/Backup all unavailable or at capacity',
            'next_action' => 'Fallback to TIER 7 - search entire hotel',
        ]);
        
        return null;
    }
    private function selectFromHotelStaff(HotelFloor $floor, HotelShift $shift): ?Waiter
    {
        Log::warning('🔍 [TIER 7] Floor staff unavailable, searching entire hotel', [
            'floor_id' => $floor->id,
            'floor_number' => $floor->floor_number,
            'shift_id' => $shift->id,
            'timestamp' => now(),
            'reason' => 'All primary/secondary/backup waiters for this floor are unavailable',
        ]);

        try {
            $totalActive = Waiter::where('status', 'active')->count();
            
            Log::debug('[TIER 7] Total active waiters in hotel', [
                'count' => $totalActive,
            ]);

            $availableWaiters = Waiter::where('status', 'active')  // Must be employed and active
                ->where('availability', 'available')               // Must not be busy/on_break/offline
                ->whereRaw('current_orders < maximum_orders')      // Must have capacity
                ->with(['user', 'floorAssignments' => function ($q) {
                    $q->where('assignment_date', today())
                      ->where('status', 'active');
                }])
                ->get();

            Log::info('[TIER 7] Hotel staff eligibility check', [
                'total_active_waiters' => $totalActive,
                'available_with_capacity' => $availableWaiters->count(),
                'floor_id' => $floor->id,
                'floor_number' => $floor->floor_number,
            ]);

            if ($availableWaiters->isEmpty()) {
                Log::warning('❌ [TIER 7] No available waiters in entire hotel', [
                    'floor_id' => $floor->id,
                    'timestamp' => now(),
                    'action' => 'Will create waiting_assignment',
                ]);
                return null;
            }
            $detailedEvaluation = $availableWaiters->map(function ($waiter) {
                $remainingCapacity = $waiter->maximum_orders - $waiter->current_orders;
                
                Log::debug('[TIER 7] Evaluating waiter from hotel pool', [
                    'waiter_id' => $waiter->id,
                    'name' => $waiter->user->name ?? 'Unknown',
                    'current_orders' => $waiter->current_orders,
                    'maximum_orders' => $waiter->maximum_orders,
                    'available_capacity' => $remainingCapacity,
                    'utilization_percent' => round(($waiter->current_orders / $waiter->maximum_orders) * 100, 2),
                    'current_assignment_floors' => $waiter->floorAssignments->pluck('floor.floor_number')->toArray(),
                ]);
                
                return [
                    'waiter' => $waiter,
                    'current_orders' => $waiter->current_orders,
                    'remaining_capacity' => $remainingCapacity,
                ];
            })->toArray();

            // STEP 4: Select waiter with LOWEST current_orders (workload balancing)
            $bestWaiterData = collect($detailedEvaluation)
                ->sortBy('current_orders')
                ->first();

            $bestWaiter = $bestWaiterData['waiter'];

            Log::info(' [TIER 7] Best available waiter selected from entire hotel', [
                'waiter_id' => $bestWaiter->id,
                'name' => $bestWaiter->user->name ?? 'Unknown',
                'current_orders' => $bestWaiter->current_orders,
                'maximum_orders' => $bestWaiter->maximum_orders,
                'available_slots' => $bestWaiterData['remaining_capacity'],
                'workload_percent' => round(($bestWaiter->current_orders / $bestWaiter->maximum_orders) * 100, 2),
                'selection_reason' => 'Lowest current workload among available waiters',
                'other_candidates_available' => $availableWaiters->count() - 1,
            ]);

            return $bestWaiter;

        } catch (Throwable $e) {
            Log::error('[TIER 7] Error searching hotel staff', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
    private function isWaiterEligible(Waiter $waiter, HotelShift $shift, string $priority = ''): bool
    {
        if ($waiter->status !== 'active') {
            Log::debug('[VALIDATION] ❌ Status check failed', [
                'waiter_id' => $waiter->id,
                'name' => $waiter->user->name,
                'status' => $waiter->status,
                'expected' => 'active',
                'priority' => $priority,
            ]);
            return false;
        }
        if ($waiter->availability !== 'available') {
            Log::debug('[VALIDATION] ❌ Availability check failed', [
                'waiter_id' => $waiter->id,
                'name' => $waiter->user->name,
                'availability' => $waiter->availability,
                'expected' => 'available',
                'reason' => 'Cannot deliver while busy/on_break/offline',
                'priority' => $priority,
            ]);
            return false;
        }

        if ($waiter->current_orders >= $waiter->maximum_orders) {
            Log::debug('[VALIDATION] ❌ Workload check failed', [
                'waiter_id' => $waiter->id,
                'name' => $waiter->user->name,
                'current_orders' => $waiter->current_orders,
                'maximum_orders' => $waiter->maximum_orders,
                'reason' => 'At maximum capacity',
                'priority' => $priority,
            ]);
            return false;
        }

        // ALL CHECKS PASSED
        Log::debug('[VALIDATION]  All checks passed', [
            'waiter_id' => $waiter->id,
            'name' => $waiter->user->name,
            'status' => $waiter->status,
            'availability' => $waiter->availability,
            'workload' => "{$waiter->current_orders}/{$waiter->maximum_orders}",
            'priority' => $priority,
        ]);

        return true;
    }
    private function getIneligibilityReason(Waiter $waiter, HotelShift $shift): string
    {
        if ($waiter->status !== 'active') {
            return "Status: {$waiter->status} (not active)";
        }
        
        if ($waiter->availability !== 'available') {
            return "Availability: {$waiter->availability} (not available)";
        }
        
        if ($waiter->current_orders >= $waiter->maximum_orders) {
            return "Workload: {$waiter->current_orders}/{$waiter->maximum_orders} (at capacity)";
        }
        
        return "Unknown reason";
    }
    public function getSelectionReport(HotelFloor $floor, HotelShift $shift): array
    {
        $floorAssignments = WaiterFloorAssignment::where([
            ['floor_id' => $floor->id],
            ['shift_id' => $shift->id],
            ['assignment_date' => today()],
            ['status' => 'active'],
        ])
        ->with(['waiter', 'waiter.user'])
        ->orderBy('priority', 'asc')
        ->get();

        $floorStaffReport = $floorAssignments->map(function ($assignment) {
            $waiter = $assignment->waiter;
            return [
                'priority' => $assignment->priority,
                'waiter_id' => $waiter->id,
                'name' => $waiter->user->name,
                'status' => $waiter->status,
                'availability' => $waiter->availability,
                'current_orders' => $waiter->current_orders,
                'maximum_orders' => $waiter->maximum_orders,
                'eligible' => $waiter->status === 'active' &&
                            $waiter->availability === 'available' &&
                            $waiter->current_orders < $waiter->maximum_orders,
            ];
        })->toArray();

        $hotelStaff = Waiter::where('status', 'active')
            ->where('availability', 'available')
            ->whereRaw('current_orders < maximum_orders')
            ->with(['user', 'floorAssignments' => function ($q) {
                $q->where('assignment_date', today());
            }])
            ->get()
            ->sortBy('current_orders');

        $hotelStaffReport = $hotelStaff->map(function ($waiter) {
            return [
                'waiter_id' => $waiter->id,
                'name' => $waiter->user->name,
                'status' => $waiter->status,
                'availability' => $waiter->availability,
                'current_orders' => $waiter->current_orders,
                'maximum_orders' => $waiter->maximum_orders,
                'assigned_floor' => $waiter->floorAssignments()->first()?->floor->floor_number ?? 'unassigned',
            ];
        })->toArray();

        return [
            'timestamp' => now(),
            'floor' => [
                'id' => $floor->id,
                'number' => $floor->floor_number,
                'name' => $floor->name,
            ],
            'shift' => [
                'id' => $shift->id,
                'name' => $shift->name,
            ],
            'floor_staff' => $floorStaffReport,
            'hotel_staff' => $hotelStaffReport,
            'available_from_floor' => collect($floorStaffReport)->where('eligible', true)->count(),
            'available_from_hotel' => count($hotelStaffReport),
        ];
    }
}
