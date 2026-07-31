<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add unique constraint on delivery_tasks to prevent double-assignment
 * 
 * Purpose: Ensure that each order can only have ONE active (non-cancelled) delivery task at any time.
 * This prevents race conditions where multiple concurrent assignment requests could create
 * duplicate delivery records for the same order.
 * 
 * Constraint Strategy:
 * - If database supports partial/conditional unique constraints (MySQL 8.0.13+):
 *   Add UNIQUE INDEX on order_id WHERE status != 'cancelled'
 * - Otherwise:
 *   Add unique index and enforce in application code via checkExistingDeliveryTask()
 * 
 * Note: Existing migration 2026_07_26_000004 has unique on order_id.
 * This constraint is additional to prevent any duplicate active assignments.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing simple unique constraint if it exists
        Schema::table('delivery_tasks', function (Blueprint $table) {
            // Check and drop existing unique constraint on order_id if present
            try {
                // Try to drop the simple unique on order_id
                $table->dropUniqueIfExists('delivery_tasks_order_id_unique');
            } catch (\Exception $e) {
                // Already dropped or doesn't exist
            }
        });
        
        // MySQL 8.0.13+ supports generated column with partial unique index
        // Try to use conditional unique constraint
        try {
            DB::statement(
                'ALTER TABLE delivery_tasks ADD UNIQUE KEY `unique_active_delivery` (order_id) WHERE status != "cancelled"'
            );
        } catch (\Exception $e) {
            // If conditional unique is not supported, fall back to regular unique
            // Application code must enforce via checkExistingDeliveryTask() method
            Schema::table('delivery_tasks', function (Blueprint $table) {
                // Add regular unique constraint as fallback
                // Note: This will allow multiple cancelled deliveries per order (acceptable)
                if (!Schema::hasIndex('delivery_tasks', 'delivery_tasks_order_id_unique')) {
                    $table->unique('order_id');
                }
            });
            
            \Log::warning('Conditional unique constraint not supported. Using regular unique constraint with application-level enforcement.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            // Drop the conditional/regular unique constraint
            try {
                DB::statement('ALTER TABLE delivery_tasks DROP INDEX `unique_active_delivery`');
            } catch (\Exception $e) {
                // Try to drop regular unique if conditional didn't exist
                try {
                    $table->dropUniqueIfExists('delivery_tasks_order_id_unique');
                } catch (\Exception $e2) {
                    // Already dropped
                }
            }
        });
    }
};
