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
        if (Schema::hasTable('delivery_tasks')) {
            try {
                // Modify floor_id column to be nullable
                DB::statement('ALTER TABLE delivery_tasks MODIFY COLUMN floor_id CHAR(36) NULL');
            } catch (\Throwable $e) {
                \Log::warning('Could not alter floor_id to nullable via statement: ' . $e->getMessage());
                try {
                    Schema::table('delivery_tasks', function (Blueprint $table) {
                        $table->uuid('floor_id')->nullable()->change();
                    });
                } catch (\Throwable $ex) {
                    \Log::error('Schema change for floor_id failed: ' . $ex->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep as nullable to prevent breaks
    }
};
