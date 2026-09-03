<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rooms
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'hotel_id')) {
            try {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->dropUnique(['room_number']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'room_number']);
                });
            } catch (\Throwable $e) {}
        }

        // 2. Room Types
        if (Schema::hasTable('room_types') && Schema::hasColumn('room_types', 'hotel_id')) {
            try {
                Schema::table('room_types', function (Blueprint $table) {
                    $table->dropUnique(['name']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('room_types', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'name']);
                });
            } catch (\Throwable $e) {}
        }

        // 3. Hotel Floors
        if (Schema::hasTable('hotel_floors') && Schema::hasColumn('hotel_floors', 'hotel_id')) {
            try {
                Schema::table('hotel_floors', function (Blueprint $table) {
                    $table->dropUnique(['floor_number']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('hotel_floors', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'floor_number']);
                });
            } catch (\Throwable $e) {}
        }

        // 4. Hotel Shifts
        if (Schema::hasTable('hotel_shifts') && Schema::hasColumn('hotel_shifts', 'hotel_id')) {
            try {
                Schema::table('hotel_shifts', function (Blueprint $table) {
                    $table->dropUnique(['name']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('hotel_shifts', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'name']);
                });
            } catch (\Throwable $e) {}
        }

        // 5. Categories
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'hotel_id')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropUnique(['slug']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropUnique(['name']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'slug']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'name']);
                });
            } catch (\Throwable $e) {}
        }

        // 6. Restaurant Tables
        if (Schema::hasTable('restaurant_tables') && Schema::hasColumn('restaurant_tables', 'hotel_id')) {
            try {
                Schema::table('restaurant_tables', function (Blueprint $table) {
                    $table->dropUnique(['table_number']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('restaurant_tables', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'table_number']);
                });
            } catch (\Throwable $e) {}
        }

        // 7. Orders
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'hotel_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropUnique(['order_number']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'order_number']);
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rooms')) {
            try {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->dropUnique(['hotel_id', 'room_number']);
                    $table->unique('room_number');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('room_types')) {
            try {
                Schema::table('room_types', function (Blueprint $table) {
                    $table->dropUnique(['hotel_id', 'name']);
                    $table->unique('name');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('hotel_floors')) {
            try {
                Schema::table('hotel_floors', function (Blueprint $table) {
                    $table->dropUnique(['hotel_id', 'floor_number']);
                    $table->unique('floor_number');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('hotel_shifts')) {
            try {
                Schema::table('hotel_shifts', function (Blueprint $table) {
                    $table->dropUnique(['hotel_id', 'name']);
                    $table->unique('name');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('categories')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropUnique(['hotel_id', 'slug']);
                    $table->dropUnique(['hotel_id', 'name']);
                    $table->unique('slug');
                    $table->unique('name');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('restaurant_tables')) {
            try {
                Schema::table('restaurant_tables', function (Blueprint $table) {
                    $table->dropUnique(['hotel_id', 'table_number']);
                    $table->unique('table_number');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropUnique(['hotel_id', 'order_number']);
                    $table->unique('order_number');
                });
            } catch (\Throwable $e) {}
        }
    }
};
