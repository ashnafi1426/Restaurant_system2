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
        // 1. Roles table indexes
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasIndex('roles', 'roles_hotel_id_is_active_index')) {
                    $table->index(['hotel_id', 'is_active'], 'roles_hotel_id_is_active_index');
                }
                if (!Schema::hasIndex('roles', 'roles_hotel_id_name_index')) {
                    $table->index(['hotel_id', 'name'], 'roles_hotel_id_name_index');
                }
            });
        }

        // 2. User Roles table: Compound index for role counts by hotel
        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                if (!Schema::hasIndex('user_roles', 'user_roles_hotel_id_role_id_index')) {
                    $table->index(['hotel_id', 'role_id'], 'user_roles_hotel_id_role_id_index');
                }
            });
        }

        // 3. Hotel Users table: Compound index for role_id grouping
        if (Schema::hasTable('hotel_users')) {
            Schema::table('hotel_users', function (Blueprint $table) {
                if (!Schema::hasIndex('hotel_users', 'hotel_users_hotel_id_role_id_index')) {
                    $table->index(['hotel_id', 'role_id'], 'hotel_users_hotel_id_role_id_index');
                }
            });
        }

        // 4. Permissions table: Compound index for module and name ordering
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasIndex('permissions', 'permissions_module_name_index')) {
                    $table->index(['module', 'name'], 'permissions_module_name_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                try { $table->dropIndex('roles_hotel_id_is_active_index'); } catch (\Throwable $e) {}
                try { $table->dropIndex('roles_hotel_id_name_index'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                try { $table->dropIndex('user_roles_hotel_id_role_id_index'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('hotel_users')) {
            Schema::table('hotel_users', function (Blueprint $table) {
                try { $table->dropIndex('hotel_users_hotel_id_role_id_index'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                try { $table->dropIndex('permissions_module_name_index'); } catch (\Throwable $e) {}
            });
        }
    }
};
