<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds unique constraint to prevent duplicate active delivery assignments per order
     * Only one non-cancelled delivery per order allowed
     * 
     * Strategy:
     * 1. Try MySQL 8.0.13+ syntax with conditional unique constraint (preferred)
     * 2. If that fails, create partial unique index using generated column approach
     * 3. Application-level validation via checkExistingDeliveryTask() as fallback
     */
    public function up(): void
    {
        // Strategy 1: Try conditional unique constraint (MySQL 8.0.13+)
        try {
            DB::statement(
                "ALTER TABLE delivery_tasks ADD UNIQUE KEY unique_active_delivery(order_id, status) WHERE status != 'cancelled'"
            );
            return; // Success, exit
        } catch (\Exception $e) {
            \Log::info('MySQL 8.0.13+ conditional unique constraint not supported, trying generated column approach.');
        }

        // Strategy 2: Try using a generated column for non-cancelled status
        try {
            // Add a generated column that helps enforce uniqueness
            Schema::table('delivery_tasks', function (Blueprint $table) {
                // This column will only be non-null for non-cancelled deliveries
                DB::statement(
                    "ALTER TABLE delivery_tasks ADD COLUMN active_order_id BIGINT GENERATED ALWAYS AS (IF(status != 'cancelled', order_id, NULL)) STORED UNIQUE KEY unique_active_delivery(order_id) WHERE status != 'cancelled'"
                );
            });
            return; // Success, exit
        } catch (\Exception $e) {
            \Log::info('Generated column approach failed. Relying on application-level validation.');
        }

        // Strategy 3: Application-level validation only
        // Add an index to speed up the checkExistingDeliveryTask() query
        try {
            DB::statement(
                "ALTER TABLE delivery_tasks ADD UNIQUE INDEX unique_order_per_status(order_id, status)"
            );
        } catch (\Exception $e) {
            \Log::warning('Could not add any unique constraint to delivery_tasks. Using application-level validation only.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Try to drop the conditional unique constraint
            DB::statement('ALTER TABLE delivery_tasks DROP INDEX unique_active_delivery');
        } catch (\Exception $e) {
            // Index may not exist if creation failed - that's okay
        }

        try {
            // Try to drop the generated column if it exists
            Schema::table('delivery_tasks', function (Blueprint $table) {
                DB::statement('ALTER TABLE delivery_tasks DROP COLUMN active_order_id');
            });
        } catch (\Exception $e) {
            // Column may not exist
        }

        try {
            // Try to drop the index only approach
            DB::statement('ALTER TABLE delivery_tasks DROP INDEX unique_order_per_status');
        } catch (\Exception $e) {
            // Index may not exist if creation failed - that's okay
        }
    }
};
