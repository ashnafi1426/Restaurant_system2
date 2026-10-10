<?php

namespace App\Services\Manager;

use App\Models\WaiterTableAssignment;
use App\Models\Waiter;
use App\Models\RestaurantTable;
use App\Models\HotelShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

class WaiterTableAssignmentService
{
    public function ensureTableExists()
    {
        try {
            if (!Schema::hasTable('waiter_table_assignments')) {
                Schema::create('waiter_table_assignments', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('hotel_id')->nullable()->index();
                    $table->unsignedBigInteger('waiter_id')->index();
                    $table->uuid('table_id')->index();
                    $table->uuid('shift_id')->nullable()->index();
                    $table->date('assignment_date')->index();
                    $table->string('priority', 50)->default('primary');
                    $table->string('status', 50)->default('active')->index();
                    $table->uuid('assigned_by')->nullable();
                    $table->timestamps();
                });

                Log::info('Created waiter_table_assignments table successfully');
            }

            if (!Schema::hasColumn('waiter_table_assignments', 'hotel_id')) {
                Schema::table('waiter_table_assignments', function (Blueprint $table) {
                    $table->uuid('hotel_id')->nullable()->after('id')->index();
                });
            }

            if (!Schema::hasColumn('waiter_table_assignments', 'assigned_by')) {
                Schema::table('waiter_table_assignments', function (Blueprint $table) {
                    $table->uuid('assigned_by')->nullable()->after('status');
                });
            }

            try {
                DB::statement("ALTER TABLE `waiter_table_assignments` MODIFY `shift_id` CHAR(36) NULL");
            } catch (\Throwable $e) {
            }

            try {
                $indexes = DB::select("
                    SELECT INDEX_NAME
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'waiter_table_assignments'
                      AND INDEX_NAME = 'unique_table_shift_date_priority'
                ");
                if (!empty($indexes)) {
                    Schema::table('waiter_table_assignments', function (Blueprint $table) {
                        $table->dropUnique('unique_table_shift_date_priority');
                    });
                }
            } catch (\Throwable $e) {
            }

            try {
                DB::statement("
                    UPDATE waiter_table_assignments wta
                    INNER JOIN restaurant_tables rt ON wta.table_id = rt.id
                    SET wta.hotel_id = rt.hotel_id
                    WHERE wta.hotel_id IS NULL OR wta.hotel_id = ''
                ");
            } catch (\Throwable $e) {
            }
        } catch (\Throwable $e) {
            Log::error('ensureTableExists error: ' . $e->getMessage());
        }
    }

    public function autoSeedAssignmentsIfEmpty()
    {
        return;
    }

    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: app(\App\Services\TenantContext::class)->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if ($hotelId) {
            app(\App\Services\TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function getAssignments(array $filters = [])
    {
        $this->ensureTableExists();

        $perPage = $filters['per_page'] ?? 100;

        if (!Schema::hasTable('waiter_table_assignments')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1);
        }

        $hotelId = $this->getHotelId();

        $query = WaiterTableAssignment::query()
            ->when($hotelId, function($q) use ($hotelId) {
                $q->where(function($sub) use ($hotelId) {
                    $sub->where('waiter_table_assignments.hotel_id', $hotelId)
                        ->orWhereHas('table', fn($t) => $t->where('hotel_id', $hotelId));
                });
            })
            ->with([
                'waiter.user',
                'table',
                'shift',
                'assignedByUser'
            ]);

        if (!empty($filters['date']) && $filters['date'] !== 'all') {
            $query->whereDate('assignment_date', $filters['date']);
        }

        if (!empty($filters['waiter_id'])) {
            $query->forWaiter($filters['waiter_id']);
        }

        if (!empty($filters['table_id'])) {
            $query->forTable($filters['table_id']);
        }

        if (!empty($filters['shift_id'])) {
            $query->forShift($filters['shift_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

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

    public function getTodayAssignments()
    {
        $this->ensureTableExists();

        if (!Schema::hasTable('waiter_table_assignments')) {
            return collect();
        }

        $hotelId = $this->getHotelId();

        return WaiterTableAssignment::with([
            'waiter.user',
            'table',
            'shift'
        ])
        ->when($hotelId, function($q) use ($hotelId) {
            $q->where(function($sub) use ($hotelId) {
                $sub->where('waiter_table_assignments.hotel_id', $hotelId)
                    ->orWhereHas('table', fn($t) => $t->where('hotel_id', $hotelId));
            });
        })
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

    public function assignWaitersToTables(array $assignments, string $assignedBy)
    {
        $created = [];
        $errors = [];

        DB::beginTransaction();

        try {
            $hotelId = $this->getHotelId();

            foreach ($assignments as $index => $assignmentData) {
                try {
                    $this->validateAssignment($assignmentData);

                    $table = RestaurantTable::withoutTenant()->find($assignmentData['table_id']);
                    $effectiveHotelId = $hotelId ?: ($table ? $table->hotel_id : null);

                    $existing = WaiterTableAssignment::where('table_id', $assignmentData['table_id'])
                        ->where('waiter_id', $assignmentData['waiter_id'])
                        ->when($effectiveHotelId && Schema::hasColumn('waiter_table_assignments', 'hotel_id'), fn($q) => $q->where('hotel_id', $effectiveHotelId))
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'status' => $assignmentData['status'] ?? WaiterTableAssignment::STATUS_ACTIVE,
                            'assigned_by' => $assignedBy,
                            'assignment_date' => $assignmentData['assignment_date'] ?? today()->toDateString(),
                            'shift_id' => $assignmentData['shift_id'] ?? $existing->shift_id,
                            'priority' => $assignmentData['priority'] ?? $existing->priority ?? WaiterTableAssignment::PRIORITY_PRIMARY,
                        ]);
                        $existing->load(['waiter.user', 'table', 'shift', 'assignedByUser']);
                        $created[] = $existing;
                        continue;
                    }

                    $resolvedShiftId = !empty($assignmentData['shift_id'])
                        ? $assignmentData['shift_id']
                        : HotelShift::where('is_active', true)->when($effectiveHotelId, fn($q) => $q->where('hotel_id', $effectiveHotelId))->value('id');

                    $createPayload = [
                        'waiter_id' => $assignmentData['waiter_id'],
                        'table_id' => $assignmentData['table_id'],
                        'shift_id' => $resolvedShiftId,
                        'assignment_date' => $assignmentData['assignment_date'] ?? today()->toDateString(),
                        'priority' => $assignmentData['priority'] ?? WaiterTableAssignment::PRIORITY_PRIMARY,
                        'status' => $assignmentData['status'] ?? WaiterTableAssignment::STATUS_ACTIVE,
                        'assigned_by' => $assignedBy,
                    ];

                    if ($effectiveHotelId && Schema::hasColumn('waiter_table_assignments', 'hotel_id')) {
                        $createPayload['hotel_id'] = $effectiveHotelId;
                    }

                    $assignment = WaiterTableAssignment::create($createPayload);

                    $assignment->load(['waiter.user', 'table', 'shift', 'assignedByUser']);
                    $created[] = $assignment;

                    Log::info('Table assignment created', [
                        'assignment_id' => $assignment->id,
                        'waiter_id' => $assignment->waiter_id,
                        'table_id' => $assignment->table_id,
                        'hotel_id' => $createPayload['hotel_id'] ?? null,
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

    public function getAssignmentStats(?string $date = null)
    {
        $hotelId = $this->getHotelId();

        $totalTablesCount = RestaurantTable::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))->count();
        $totalWaitersCount = Waiter::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))->count();

        if (!Schema::hasTable('waiter_table_assignments')) {
            return [
                'total_assignments' => 0,
                'total_tables' => $totalTablesCount,
                'total_waiters' => $totalWaitersCount,
                'primary_assignments' => 0,
                'secondary_assignments' => 0,
                'backup_assignments' => 0,
                'by_shift' => [],
            ];
        }

        $query = WaiterTableAssignment::query()
            ->when($hotelId, function($q) use ($hotelId) {
                $q->where(function($sub) use ($hotelId) {
                    $sub->where('waiter_table_assignments.hotel_id', $hotelId)
                        ->orWhereHas('table', fn($t) => $t->where('hotel_id', $hotelId));
                });
            });

        if ($date && $date !== 'all') {
            $query->whereDate('assignment_date', $date);
        }

        $assignments = $query->get();

        return [
            'total_assignments' => $assignments->count(),
            'total_tables' => $totalTablesCount,
            'total_waiters' => $totalWaitersCount > 0 ? $totalWaitersCount : $assignments->pluck('waiter_id')->unique()->count(),
            'active_assignments' => $assignments->where('status', WaiterTableAssignment::STATUS_ACTIVE)->count(),
            'primary_assignments' => $assignments->where('priority', WaiterTableAssignment::PRIORITY_PRIMARY)->count(),
            'secondary_assignments' => $assignments->where('priority', WaiterTableAssignment::PRIORITY_SECONDARY)->count(),
            'backup_assignments' => $assignments->where('priority', WaiterTableAssignment::PRIORITY_BACKUP)->count(),
            'by_shift' => $assignments->groupBy('shift_id')->map->count(),
        ];
    }

    public function getAssignedWaiterForTable(string $tableId)
    {
        $currentTime = Carbon::now()->format('H:i:s');

        $currentShift = HotelShift::where('is_active', true)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        return WaiterTableAssignment::getAssignedWaiter(
            $tableId,
            $currentShift?->id,
            today()
        );
    }

    public function getWaiterTables(int $waiterId, ?string $date = null)
    {
        return WaiterTableAssignment::getWaiterTables($waiterId, $date);
    }

    private function validateAssignment(array $data)
    {
        if (!isset($data['waiter_id']) || !is_numeric($data['waiter_id'])) {
            throw new \Exception('Valid waiter_id is required');
        }

        if (!isset($data['table_id'])) {
            throw new \Exception('table_id is required');
        }

        $waiter = Waiter::find($data['waiter_id']);
        if (!$waiter) {
            throw new \Exception('Waiter not found');
        }

        if (strtolower($waiter->status ?? 'active') !== 'active') {
            throw new \Exception("Waiter '{$waiter->name}' is inactive. Only active waiters can be assigned.");
        }

        $table = RestaurantTable::withoutTenant()->find($data['table_id']);
        if (!$table) {
            throw new \Exception('Table not found');
        }

        $hotelId = $this->getHotelId();
        if ($hotelId) {
            if ($table->hotel_id && $table->hotel_id !== $hotelId) {
                throw new \Exception('Table belongs to a different hotel');
            }
            if ($waiter->hotel_id && $waiter->hotel_id !== $hotelId) {
                throw new \Exception('Waiter belongs to a different hotel');
            }
        }

        if (!empty($data['shift_id']) && !HotelShift::find($data['shift_id'])) {
            throw new \Exception('Shift not found');
        }

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

