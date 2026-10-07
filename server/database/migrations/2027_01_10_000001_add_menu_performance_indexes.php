<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('menu_items')) {
            Schema::table('menu_items', function (Blueprint $table) {
                if (!Schema::hasIndex('menu_items', 'menu_items_hotel_id_is_available_idx')) {
                    $table->index(['hotel_id', 'is_available'], 'menu_items_hotel_id_is_available_idx');
                }
                if (!Schema::hasIndex('menu_items', 'menu_items_hotel_avail_cat_idx')) {
                    $table->index(['hotel_id', 'is_available', 'category'], 'menu_items_hotel_avail_cat_idx');
                }
            });
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (!Schema::hasIndex('categories', 'categories_hotel_active_order_idx')) {
                    $table->index(['hotel_id', 'is_active', 'display_order'], 'categories_hotel_active_order_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menu_items')) {
            Schema::table('menu_items', function (Blueprint $table) {
                try { $table->dropIndex('menu_items_hotel_id_is_available_idx'); } catch (\Throwable $e) {}
                try { $table->dropIndex('menu_items_hotel_avail_cat_idx'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                try { $table->dropIndex('categories_hotel_active_order_idx'); } catch (\Throwable $e) {}
            });
        }
    }
};
