<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('waiter_floor_assignments')) {
            try {
                // Ensure shift_id is nullable on MySQL
                DB::statement("ALTER TABLE `waiter_floor_assignments` MODIFY `shift_id` CHAR(36) NULL");
            } catch (\Throwable $e) {
                try {
                    Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                        $table->uuid('shift_id')->nullable()->change();
                    });
                } catch (\Throwable $e2) {}
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op
    }
};
