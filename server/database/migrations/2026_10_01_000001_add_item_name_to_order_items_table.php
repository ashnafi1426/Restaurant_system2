<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'item_name')) {
                    $table->string('item_name')->nullable()->after('menu_item_id');
                }
            });

            // Backfill existing order_items with menu_item name
            try {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::statement("
                        UPDATE order_items 
                        SET item_name = menu_items.name 
                        FROM menu_items 
                        WHERE order_items.menu_item_id = menu_items.id 
                        AND order_items.item_name IS NULL
                    ");
                } else {
                    DB::statement("
                        UPDATE order_items 
                        JOIN menu_items ON order_items.menu_item_id = menu_items.id 
                        SET order_items.item_name = menu_items.name 
                        WHERE order_items.item_name IS NULL
                    ");
                }
            } catch (\Throwable $e) {
                // Ignore if driver differs or tables empty
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'item_name')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('item_name');
            });
        }
    }
};
