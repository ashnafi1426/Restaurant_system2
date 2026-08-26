<?php

namespace App\Services\Manager;

use App\Models\WaiterTableAssignment;
use App\Models\Waiter;
use App\Models\RestaurantTable;
use App\Models\HotelShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for managing waiter-to-table assignments
 * Handles CRUD operations for walk-in customer table service
 */
class WaiterTableAssignmentService
{
    /**
     * Auto-seed table assignments if DB table is empty or missing assignments for today
     */
    public function autoSeedAssignmentsIfEmpty()
    {
        $tables = RestaurantTable::get();
        $waiters = Waiter::with('user')->get();
        $shift = HotelShift::first();

        if ($tables->isEmpty() || $waiters->isEmpty()) {
            return;
        }

        $today = Carbon::today()->format('Y-m-d');
        $existingTableIds = WaiterTableAssignment::where('assignment_date', $today)->pluck('table_id')->toArray();

        foreach ($tables as $index => $table) {
            if (in_array($table->id, $existingTableIds)) {
                continue;
            }
            $waiter = $waiters[$index % $waiters->count()];
            WaiterTableAssignment::create([
                'waiter_id' => $waiter->id,
                'table_id' => $table->id,
                'shift_id' => $shift ? $shift->id : null,
                'assignment_date' => $today,
                'priority' => ($index % 3 === 0) ? 'primary' : (($index % 3 === 1) ? 'secondary' : 'backup'),
                'status' => 'active',
            ]);
        }
    }

    /**
     * Get all table assignments with filters
     * 
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAssignments(array $filters = [])
    {
        $this->autoSeedAssignmentsIfEmpty();

        $query = WaiterTableAssignment::query()
            ->with([
                'waiter.user',
                'table',
                'shift',
                'assignedByUser'
            ]);

        // Filter by date if provided (and not empty/'all')
        if (!empty($filters['date']) && $filters['date'] !== 'all') {
            $filteredQuery = (clone $query)->forDate($filters['date']);
            if ($filteredQuery->count() > 0) {
                $query = $filteredQuery;
            }
        }

        // Filter by waiter
        if (!empty($filters['waiter_id'])) {
            $query->forWaiter($filters['waiter_id']);
        }

        // Filter by table
        if (!empty($filters['table_id'])) {
            $query->forTable($filters['table_id']);
        }

        // Filter by shift
        if (!empty($filters['shift_id'])) {
            $query->forShift($filters['shift_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Order by priority (primary first)
        $query->orderByRaw("
            CASE 
                WHEN priority = 'primary' THEN 1
                WHEN priority = 'secondary' THEN 2
                WHEN priority = 'backup' THEN 3
                ELSE 4
            END
        ")->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 100;
        
        return $query->paginate($perPage);
    }

    /**
     * Get today's active assignments
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodayAssignments()
    {
        return WaiterTableAssignment::with([
            'waiter.user',
            'table',
            'shift'
        ])
        ->active()
        ->today()
        ->orderByRaw("
            CASE 
                WHEN priority = 'primary' THEN 1
                WHEN priority = 'secondary' THEN 2
                WHEN priority = 'backup' THEN 3
                ELSE 4
            END
        ")
        ->get();
    }

    /**
     * Assign waiters to tables (batch assignment)
     * 
     * @param array $assignments
     * @param string $assignedBy (user_id of manager)
     * @return array
     */
    public function assignWaitersToTables(array $assignments, string $assignedBy)
    {
        $created = [];
        $errors = [];

        DB::beginTransaction();
        
        try {
            foreach ($assignments as $index => $assignmentData) {
                try {
                    // Validate required fields
                    $this->validateAssignment($assignmentData);

                    // Check if assignment already exists
                    $exists = WaiterTableAssignment::where('table_id', $assignmentData['table_id'])
                        ->where('shift_id', $assignmentData['shift_id'])
                        ->where('assignment_date', $assignmentData['assignment_date'])
                        ->where('priority', $assignmentData['priority'])
                        ->where('status', WaiterTableAssignment::STATUS_ACTIVE)
                        ->exists();

                    if ($exists) {
                        $errors[] = [
                            'index' => $index,
                            'error' => 'Assignment already exists for this table, shift, and priority'
                        ];
                        continue;
                    }

                    // Create assignment
                    $assignment = WaiterTableAssignment::create([
                        'waiter_id' => $assignmentData['waiter_id'],
                        'table_id' => $assignmentData['table_id'],
                        'shift_id' => $assignmentData['shift_id'],
                        'assignment_date' => $assignmentData['assignment_date'],
                        'priority' => $assignmentData['priority'] ?? WaiterTableAssignment::PRIORITY_PRIMARY,
                        'status' => WaiterTableAssignment::STATUS_ACTIVE,
                        'assigned_by' => $assignedBy,
                    ]);

                    $assignment->load(['waiter.user', 'table', 'shift', 'assignedByUser']);
                    $created[] = $assignment;

                    Log::info('Table assignment created', [
                        'assignment_id' => $assignment->id,
                        'waiter_id' => $assignment->waiter_id,
                        'table_id' => $assignment->table_id,
                        'shift_id' => $assignment->shift_id,
                    ]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Failed to create table assignment', [
                        'assignment_data' => $assignmentData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (count($created) === 0 && count($errors) > 0) {
                DB::rollBack();
                throw new \Exception('No assignments were created');
            }

            DB::commit();

            return [
                'success' => true,
                'created' => $created,
                'errors' => $errors,
                'total_created' => count($created),
                'total_errors' => count($errors),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch table assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update assignment priority or status
     * 
     * @param string $assignmentId
     * @param array $data
     * @return WaiterTableAssignment
     */
    public function updateAssignment(string $assignmentId, array $data)
    {
        $assignment = WaiterTableAssignment::findOrFail($assignmentId);

        $updateFields = ['waiter_id', 'table_id', 'shift_id', 'priority', 'status'];
        foreach ($updateFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $updateData[$field] = $data[$field];
            }
        }

        $assignment->update($updateData);
        $assignment->load(['waiter.user', 'table', 'shift']);

        Log::info('Table assignment updated', [
            'assignment_id' => $assignmentId,
            'updated_data' => $updateData
        ]);

        return $assignment;
    }

    /**
     * Delete assignment
     * 
     * @param string $assignmentId
     * @return bool
     */
    public function deleteAssignment(string $assignmentId)
    {
        $assignment = WaiterTableAssignment::findOrFail($assignmentId);
        
        Log::info('Deleting table assignment', [
            'assignment_id' => $assignmentId,
            'waiter_id' => $assignment->waiter_id,
            'table_id' => $assignment->table_id
        ]);

        return $assignment->delete();
    }

    /**
     * Get assignment statistics
     * 
     * @param string|null $date
     * @return array
     */
    public function getAssignmentStats(?string $date = null)
    {
        $this->autoSeedAssignmentsIfEmpty();

        $query = WaiterTableAssignment::query();

        if ($date && $date !== 'all') {
            $filteredQuery = (clone $query)->where('assignment_date', $date);
            if ($filteredQuery->count() > 0) {
                $query = $filteredQuery;
            }
        }

        $assignments = $query->get();

        $totalTablesCount = RestaurantTable::count();
        $totalWaitersCount = Waiter::count();

        return [
            'total_assignments' => $assignments->count(),
            'total_tables' => max($assignments->pluck('table_id')->unique()->count(), $totalTablesCount),
            'total_waiters' => max($assignments->pluck('waiter_id')->unique()->count(), $totalWaitersCount),
            'primary_assignments' => $assignments->where('priority', WaiterTableAssignment::PRIORITY_PRIMARY)->count(),
            'secondary_assignments' => $assignments->where('priority', WaiterTableAssignment::PRIORITY_SECONDARY)->count(),
            'backup_assignments' => $assignments->where('priority', WaiterTableAssignment::PRIORITY_BACKUP)->count(),
            'by_shift' => $assignments->groupBy('shift_id')->map->count(),
        ];
    }

    /**
     * Get assigned waiter for a table at current time
     * 
     * @param string $tableId
     * @return WaiterTableAssignment|null
     */
    public function getAssignedWaiterForTable(string $tableId)
    {
        // Get current shift based on time
        $currentTime = Carbon::now()->format('H:i:s');
        
        $currentShift = HotelShift::where('is_active', true)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        if (!$currentShift) {
            return null;
        }

        return WaiterTableAssignment::getAssignedWaiter(
            $tableId,
            $currentShift->id,
            today()
        );
    }

    /**
     * Get tables assigned to a waiter
     * 
     * @param int $waiterId
     * @param string|null $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWaiterTables(int $waiterId, ?string $date = null)
    {
        return WaiterTableAssignment::getWaiterTables($waiterId, $date);
    }

    /**
     * Validate assignment data
     * 
     * @param array $data
     * @throws \Exception
     */
    private function validateAssignment(array $data)
    {
        if (!isset($data['waiter_id']) || !is_numeric($data['waiter_id'])) {
            throw new \Exception('Valid waiter_id is required');
        }

        if (!isset($data['table_id'])) {
            throw new \Exception('table_id is required');
        }

        if (!isset($data['shift_id'])) {
            throw new \Exception('shift_id is required');
        }

        if (!isset($data['assignment_date'])) {
            throw new \Exception('assignment_date is required');
        }

        // Validate waiter exists
        if (!Waiter::find($data['waiter_id'])) {
            throw new \Exception('Waiter not found');
        }

        // Validate table exists
        if (!RestaurantTable::find($data['table_id'])) {
            throw new \Exception('Table not found');
        }

        // Validate shift exists
        if (!HotelShift::find($data['shift_id'])) {
            throw new \Exception('Shift not found');
        }

        // Validate priority
        $validPriorities = [
            WaiterTableAssignment::PRIORITY_PRIMARY,
            WaiterTableAssignment::PRIORITY_SECONDARY,
            WaiterTableAssignment::PRIORITY_BACKUP
        ];

        if (isset($data['priority']) && !in_array($data['priority'], $validPriorities)) {
            throw new \Exception('Invalid priority value');
        }
    }
}
