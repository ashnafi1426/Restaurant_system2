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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasIndex('users', 'users_first_name_last_name_index')) {
                    $table->index(['first_name', 'last_name'], 'users_first_name_last_name_index');
                }
            });
        }

        if (Schema::hasTable('hotel_users')) {
            Schema::table('hotel_users', function (Blueprint $table) {
                if (!Schema::hasIndex('hotel_users', 'hotel_users_hotel_id_is_active_index')) {
                    $table->index(['hotel_id', 'is_active'], 'hotel_users_hotel_id_is_active_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropIndex('users_first_name_last_name_index'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('hotel_users')) {
            Schema::table('hotel_users', function (Blueprint $table) {
                try { $table->dropIndex('hotel_users_hotel_id_is_active_index'); } catch (\Throwable $e) {}
            });
        }
    }
};
