<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove the incorrect wfa_floor_shift_date_priority constraint
     * Keep only the correct wfa_waiter_floor_shift_date_unique constraint
     */
    public function up(): void
    {
        // Drop the incorrect unique constraint that includes priority
        try {
            DB::statement('ALTER TABLE waiter_floor_assignments DROP INDEX wfa_floor_shift_date_priority');
            echo "Dropped incorrect constraint: wfa_floor_shift_date_priority\n";
        } catch (\Exception $e) {
            echo "Constraint wfa_floor_shift_date_priority does not exist or could not be dropped\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the constraint if needed (though it's incorrect)
        try {
            DB::statement('ALTER TABLE waiter_floor_assignments ADD UNIQUE KEY wfa_floor_shift_date_priority (floor_id, shift_id, assignment_date, priority)');
        } catch (\Exception $e) {
            echo "Could not re-add constraint\n";
        }
    }
};
