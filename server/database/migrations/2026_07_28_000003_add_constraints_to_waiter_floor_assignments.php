<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds unique constraint and indices to waiter_floor_assignments table
     * Prevents duplicate assignments of same waiter to same floor on same shift/date
     */
    public function up(): void
    {
        Schema::table('waiter_floor_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('waiter_floor_assignments', 'shift_id')) {
                $table->uuid('shift_id')->nullable();
            }
            if (!Schema::hasColumn('waiter_floor_assignments', 'assignment_date')) {
                $table->date('assignment_date')->nullable();
            }
            if (!Schema::hasColumn('waiter_floor_assignments', 'status')) {
                $table->string('status', 50)->default('assigned');
            }
            if (!Schema::hasColumn('waiter_floor_assignments', 'priority')) {
                $table->string('priority', 50)->default('primary');
            }
            if (!Schema::hasColumn('waiter_floor_assignments', 'assigned_by')) {
                $table->uuid('assigned_by')->nullable();
            }

            // Add index on assignment_date for daily assignment queries
            if (!$this->indexExists('waiter_floor_assignments', 'waiter_floor_assignments_assignment_date_index')) {
                $table->index('assignment_date', 'waiter_floor_assignments_assignment_date_index');
            }

            // Add index on shift_id for shift-based queries
            if (!$this->indexExists('waiter_floor_assignments', 'waiter_floor_assignments_shift_id_index')) {
                $table->index('shift_id', 'waiter_floor_assignments_shift_id_index');
            }

            // Add index on floor_id for floor-based queries
            if (!$this->indexExists('waiter_floor_assignments', 'waiter_floor_assignments_floor_id_index')) {
                $table->index('floor_id', 'waiter_floor_assignments_floor_id_index');
            }
        });

        // Add unique constraint preventing duplicate assignments: same waiter to same floor on same shift in same day
        if (!$this->uniqueConstraintExists('waiter_floor_assignments', 'wfa_waiter_floor_shift_date_unique')) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE waiter_floor_assignments ADD CONSTRAINT wfa_waiter_floor_shift_date_unique UNIQUE (waiter_id, floor_id, shift_id, assignment_date)');
            } else {
                DB::statement('ALTER TABLE waiter_floor_assignments ADD UNIQUE KEY wfa_waiter_floor_shift_date_unique (waiter_id, floor_id, shift_id, assignment_date)');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiter_floor_assignments', function (Blueprint $table) {
            // Drop indices if they exist
            $this->dropIndexIfExists('waiter_floor_assignments', 'waiter_floor_assignments_assignment_date_index');
            $this->dropIndexIfExists('waiter_floor_assignments', 'waiter_floor_assignments_shift_id_index');
            $this->dropIndexIfExists('waiter_floor_assignments', 'waiter_floor_assignments_floor_id_index');
        });

        // Drop unique constraint
        if ($this->uniqueConstraintExists('waiter_floor_assignments', 'wfa_waiter_floor_shift_date_unique')) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE waiter_floor_assignments DROP CONSTRAINT IF EXISTS wfa_waiter_floor_shift_date_unique');
            } else {
                DB::statement('ALTER TABLE waiter_floor_assignments DROP INDEX wfa_waiter_floor_shift_date_unique');
            }
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
            return count($indexes) > 0;
        }
        $indexes = DB::select("SELECT * FROM information_schema.STATISTICS WHERE TABLE_NAME=? AND INDEX_NAME=?", [$table, $indexName]);
        return count($indexes) > 0;
    }

    /**
     * Check if unique constraint exists
     */
    private function uniqueConstraintExists(string $table, string $constraintName): bool
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $constraints = DB::select("SELECT conname FROM pg_constraint WHERE conname = ?", [$constraintName]);
            return count($constraints) > 0;
        }
        $constraints = DB::select("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME=? AND CONSTRAINT_NAME=?", [$table, $constraintName]);
        return count($constraints) > 0;
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement("DROP INDEX IF EXISTS $indexName");
            } else {
                DB::statement("ALTER TABLE $table DROP INDEX $indexName");
            }
        }
    }
};
