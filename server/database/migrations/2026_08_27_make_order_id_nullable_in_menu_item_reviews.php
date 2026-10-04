<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allow order_id to be nullable for QR-only guest reviews
     */
    public function up(): void
    {
        // Check if the table exists first
        if (Schema::hasTable('menu_item_reviews')) {
            // First, clear any invalid order_id references (set them to NULL if they don't exist in orders table)
            DB::statement('
                UPDATE menu_item_reviews
                SET order_id = NULL
                WHERE order_id IS NOT NULL 
                AND order_id NOT IN (SELECT id FROM orders)
            ');

            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE menu_item_reviews DROP CONSTRAINT IF EXISTS unique_review_per_order');
                DB::statement('ALTER TABLE menu_item_reviews DROP CONSTRAINT IF EXISTS menu_item_reviews_order_id_foreign');
                DB::statement('ALTER TABLE menu_item_reviews ALTER COLUMN order_id DROP NOT NULL');
            } else {
                Schema::table('menu_item_reviews', function (Blueprint $table) {
                    try {
                        DB::statement('ALTER TABLE menu_item_reviews DROP INDEX unique_review_per_order');
                    } catch (\Exception $e) {}
                    try {
                        DB::statement('ALTER TABLE menu_item_reviews DROP FOREIGN KEY menu_item_reviews_order_id_foreign');
                    } catch (\Exception $e) {}
                });

                // Modify column to nullable using raw SQL
                DB::statement('ALTER TABLE menu_item_reviews MODIFY COLUMN order_id CHAR(36) NULL');
            }

            // Re-add foreign key with nullOnDelete
            Schema::table('menu_item_reviews', function (Blueprint $table) {
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menu_item_reviews')) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                try {
                    DB::statement('ALTER TABLE menu_item_reviews DROP CONSTRAINT IF EXISTS menu_item_reviews_order_id_foreign');
                    DB::statement('ALTER TABLE menu_item_reviews ALTER COLUMN order_id SET NOT NULL');
                } catch (\Exception $e) {}
            } else {
                Schema::table('menu_item_reviews', function (Blueprint $table) {
                    try {
                        DB::statement('ALTER TABLE menu_item_reviews DROP FOREIGN KEY menu_item_reviews_order_id_foreign');
                    } catch (\Exception $e) {}
                });

                // Make order_id NOT NULL again
                DB::statement('ALTER TABLE menu_item_reviews MODIFY COLUMN order_id CHAR(36) NOT NULL');
            }

            Schema::table('menu_item_reviews', function (Blueprint $table) {
                // Re-add the original foreign key
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->onDelete('restrict');
            });
        }
    }
};
