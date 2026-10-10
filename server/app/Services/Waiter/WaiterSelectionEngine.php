<?php

namespace App\Services\Waiter;

use App\Models\Waiter;
use App\Models\Floor;
use App\Models\HotelShift;
use App\Models\RestaurantTable;
use App\Models\WaiterFloorAssignment;
use App\Models\WaiterTableAssignment;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Log;
use Throwable;

class WaiterSelectionEngine
{
    /**
     * Select the best eligible waiter for room service on a specific floor.
     * Flow: Room -> Floor -> Floor Waiters -> Workload -> Assigned Waiter
     */
    public function selectBestWaiter($floor, ?HotelShift $shift = null): ?Waiter
    {
        if (!$floor || empty($floor->id)) {
            return null;
        }

        $hotelId = $floor->hotel_id ?? app(TenantContext::class)->getHotelId();

        try {

            $query = Waiter::query()
                ->select('waiters.*')
                ->join('waiter_floor_assignments', 'waiter_floor_assignments.waiter_id', '=', 'waiters.id')
                ->where('waiter_floor_assignments.floor_id', $floor->id)
                ->where(function ($q) {
                    $q->where('waiter_floor_assignments.is_active', true)
                      ->orWhere('waiter_floor_assignments.status', 'active');
                })
                ->where('waiters.status', 'active')
                ->when($hotelId, fn($q) => $q->where('waiters.hotel_id', $hotelId));

            $hasTodayAssignment = (clone $query)
                ->whereDate('waiter_floor_assignments.assignment_date', today())
                ->exists();

            if ($hasTodayAssignment) {
                $query->whereDate('waiter_floor_assignments.assignment_date', today());
            } else {

                $query->where(function ($q) {
                    $q->whereNull('waiter_floor_assignments.assignment_date')
                      ->orWhere('waiter_floor_assignments.is_active', true)
                      ->orWhere('waiter_floor_assignments.status', 'active');
                });
            }

            if ($shift && !empty($shift->id)) {
                $hasShiftMatch = (clone $query)->where('waiter_floor_assignments.shift_id', $shift->id)->exists();
                if ($hasShiftMatch) {
                    $query->where(function ($q) use ($shift) {
                        $q->where('waiter_floor_assignments.shift_id', $shift->id)
                          ->orWhereNull('waiter_floor_assignments.shift_id');
                    });
                }
            }

            $waiter = (clone $query)
                ->where(function ($q) {
                    $q->where('waiters.availability', '!=', 'offline')
                      ->orWhereNull('waiters.availability');
                })
                ->where(function ($q) {
                    $q->whereNull('waiters.maximum_orders')
                      ->orWhereRaw('waiters.current_orders < waiters.maximum_orders');
                })
                ->with('user')
                ->orderBy('waiters.current_orders', 'asc')
                ->orderByRaw("COALESCE(waiters.last_assigned_at, '1970-01-01 00:00:00') ASC")
                ->orderBy('waiters.id', 'asc')
                ->first();

            if ($waiter) {
                Log::info('[WaiterSelection] Floor waiter selected', [
                    'floor_id' => $floor->id,
                    'waiter_id' => $waiter->id,
                    'current_orders' => $waiter->current_orders,
                ]);
                return $waiter;
            }

            $fallbackFloorWaiter = (clone $query)
                ->with('user')
                ->orderBy('waiters.current_orders', 'asc')
                ->orderByRaw("COALESCE(waiters.last_assigned_at, '1970-01-01 00:00:00') ASC")
                ->orderBy('waiters.id', 'asc')
                ->first();

            if ($fallbackFloorWaiter) {
                Log::info('[WaiterSelection] Fallback floor waiter selected on same floor', [
                    'floor_id' => $floor->id,
                    'waiter_id' => $fallbackFloorWaiter->id,
                    'current_orders' => $fallbackFloorWaiter->current_orders,
                ]);
                return $fallbackFloorWaiter;
            }

            $hotelFallbackWaiter = Waiter::query()
                ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                ->where('status', 'active')
                ->with('user')
                ->orderBy('current_orders', 'asc')
                ->orderByRaw("COALESCE(last_assigned_at, '1970-01-01 00:00:00') ASC")
                ->orderBy('id', 'asc')
                ->first();

            if ($hotelFallbackWaiter) {
                Log::warning('[WaiterSelection] Floor has no waiter, assigned to available hotel waiter', [
                    'floor_id' => $floor->id,
                    'floor_name' => $floor->name ?? 'Floor ' . ($floor->floor_number ?? ''),
                    'waiter_id' => $hotelFallbackWaiter->id,
                ]);
                return $hotelFallbackWaiter;
            }

            Log::warning('[WaiterSelection] No waiter available anywhere in hotel', [
                'floor_id' => $floor->id,
                'hotel_id' => $hotelId,
            ]);
            return null;

        } catch (Throwable $e) {
            Log::error('[WaiterSelection] Error querying floor waiters', [
                'floor_id' => $floor->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Select the best eligible waiter for a restaurant table.
     * Flow: Restaurant Table -> Section -> Eligible Waiters -> Workload -> Assigned Waiter
     */
    public function selectWaiterForTable(RestaurantTable $table, ?HotelShift $shift = null): ?Waiter
    {
        $hotelId = $table->hotel_id ?? app(TenantContext::class)->getHotelId();

        try {

            $tableAssignmentQuery = Waiter::query()
                ->select('waiters.*')
                ->join('waiter_table_assignments', 'waiter_table_assignments.waiter_id', '=', 'waiters.id')
                ->where('waiter_table_assignments.table_id', $table->id)
                ->where('waiter_table_assignments.status', 'active')
                ->where('waiters.status', 'active')
                ->when($hotelId, fn($q) => $q->where('waiters.hotel_id', $hotelId));

            $hasTodayTableAssignment = (clone $tableAssignmentQuery)
                ->whereDate('waiter_table_assignments.assignment_date', today())
                ->exists();

            if ($hasTodayTableAssignment) {
                $tableAssignmentQuery->whereDate('waiter_table_assignments.assignment_date', today());
            } else {
                $tableAssignmentQuery->where(function ($q) {
                    $q->whereNull('waiter_table_assignments.assignment_date')
                      ->orWhere('waiter_table_assignments.status', 'active');
                });
            }

            $tableWaiter = $tableAssignmentQuery
                ->with('user')
                ->orderBy('waiters.current_orders', 'asc')
                ->orderByRaw("COALESCE(waiters.last_assigned_at, '1970-01-01 00:00:00') ASC")
                ->first();

            if ($tableWaiter) {
                Log::info('[WaiterSelection] Direct table waiter selected', [
                    'table_id' => $table->id,
                    'waiter_id' => $tableWaiter->id,
                ]);
                return $tableWaiter;
            }

            $section = $table->section_name ?: $table->section ?: $table->location;
            if (!empty($section)) {
                $sectionWaiter = Waiter::query()
                    ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->where('availability', '!=', 'offline')
                          ->orWhereNull('availability');
                    })
                    ->where(function ($q) use ($section) {
                        $q->whereRaw('LOWER(section) = ?', [strtolower($section)])
                          ->orWhereRaw('LOWER(section) LIKE ?', ['%' . strtolower($section) . '%']);
                    })
                    ->where(function ($q) {
                        $q->whereNull('maximum_orders')
                          ->orWhereRaw('current_orders < maximum_orders');
                    })
                    ->with('user')
                    ->orderBy('current_orders', 'asc')
                    ->orderByRaw("COALESCE(last_assigned_at, '1970-01-01 00:00:00') ASC")
                    ->first();

                if ($sectionWaiter) {
                    Log::info('[WaiterSelection] Section waiter selected for table', [
                        'table_id' => $table->id,
                        'section' => $section,
                        'waiter_id' => $sectionWaiter->id,
                        'current_orders' => $sectionWaiter->current_orders,
                    ]);
                    return $sectionWaiter;
                }
            }
        } catch (Throwable $e) {
            Log::error('[WaiterSelection] Error selecting waiter for table', [
                'table_id' => $table->id,
                'error' => $e->getMessage(),
            ]);
        }

        $fallbackWaiter = Waiter::query()
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('availability', '!=', 'offline')
                  ->orWhereNull('availability');
            })
            ->where(function ($q) {
                $q->whereNull('maximum_orders')
                  ->orWhereRaw('current_orders < maximum_orders');
            })
            ->with('user')
            ->orderBy('current_orders', 'asc')
            ->orderByRaw("COALESCE(last_assigned_at, '1970-01-01 00:00:00') ASC")
            ->first();

        if ($fallbackWaiter) {
            Log::info('[WaiterSelection] Hotel fallback waiter selected for table', [
                'table_id' => $table->id,
                'waiter_id' => $fallbackWaiter->id,
            ]);
            return $fallbackWaiter;
        }

        return Waiter::query()
            ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
            ->where('status', 'active')
            ->with('user')
            ->orderBy('current_orders', 'asc')
            ->first();
    }

    /**
     * Check if a waiter is eligible to receive orders.
     */
    public function isWaiterEligible(Waiter $waiter): bool
    {
        return $waiter->status === 'active'
            && $waiter->availability !== 'offline'
            && ($waiter->maximum_orders === null || $waiter->current_orders < $waiter->maximum_orders);
    }
}

