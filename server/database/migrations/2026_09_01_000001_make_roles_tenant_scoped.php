<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles table: Add hotel_id & update unique constraints
        if (Schema::hasTable('roles')) {
            if (!Schema::hasColumn('roles', 'hotel_id')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->uuid('hotel_id')->nullable()->after('id')->index();
                    $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
                });
            }

            // Drop old global unique index on slug
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_slug_unique');
            } else {
                try {
                    Schema::table('roles', function (Blueprint $table) {
                        $table->dropUnique(['slug']);
                    });
                } catch (\Throwable $e) {}
            }

            // Add compound unique index on [hotel_id, slug]
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'slug'], 'roles_hotel_id_slug_unique');
                });
            } catch (\Throwable $e) {}
        }

        // 2. User Roles table: Add hotel_id & update unique constraints
        if (Schema::hasTable('user_roles')) {
            if (!Schema::hasColumn('user_roles', 'hotel_id')) {
                Schema::table('user_roles', function (Blueprint $table) {
                    $table->uuid('hotel_id')->nullable()->after('user_id')->index();
                    $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
                });
            }

            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE user_roles DROP CONSTRAINT IF EXISTS user_roles_user_id_role_id_unique');
            } else {
                try {
                    Schema::table('user_roles', function (Blueprint $table) {
                        $table->dropUnique(['user_id', 'role_id']);
                    });
                } catch (\Throwable $e) {}
            }

            try {
                Schema::table('user_roles', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'user_id', 'role_id'], 'user_roles_hotel_user_role_unique');
                });
            } catch (\Throwable $e) {}
        }

        // 3. User Permissions table: Add hotel_id & update unique constraints
        if (Schema::hasTable('user_permissions')) {
            if (!Schema::hasColumn('user_permissions', 'hotel_id')) {
                Schema::table('user_permissions', function (Blueprint $table) {
                    $table->uuid('hotel_id')->nullable()->after('user_id')->index();
                    $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
                });
            }

            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE user_permissions DROP CONSTRAINT IF EXISTS user_permissions_user_id_permission_id_unique');
            } else {
                try {
                    Schema::table('user_permissions', function (Blueprint $table) {
                        $table->dropUnique(['user_id', 'permission_id']);
                    });
                } catch (\Throwable $e) {}
            }

            try {
                Schema::table('user_permissions', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'user_id', 'permission_id'], 'user_permissions_hotel_user_perm_unique');
                });
            } catch (\Throwable $e) {}
        }

        // 4. Hotel Users table: Add role_id link
        if (Schema::hasTable('hotel_users')) {
            if (!Schema::hasColumn('hotel_users', 'role_id')) {
                Schema::table('hotel_users', function (Blueprint $table) {
                    $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
                });
            }
        }

        // 5. Backfill existing roles for all current hotels
        $this->backfillTenantRoles();
    }

    protected function backfillTenantRoles(): void
    {
        if (!Schema::hasTable('hotels') || !Schema::hasTable('roles')) {
            return;
        }

        $hotels = DB::table('hotels')->get();
        // Global template roles where hotel_id is null
        $templateRoles = DB::table('roles')->whereNull('hotel_id')->get();

        if ($templateRoles->isEmpty()) {
            return;
        }

        foreach ($hotels as $hotel) {
            foreach ($templateRoles as $templateRole) {
                // Check if this hotel already has this role
                $existingRole = DB::table('roles')
                    ->where('hotel_id', $hotel->id)
                    ->where('slug', $templateRole->slug)
                    ->first();

                if (!$existingRole) {
                    $newRoleId = DB::table('roles')->insertGetId([
                        'hotel_id' => $hotel->id,
                        'name' => $templateRole->name,
                        'slug' => $templateRole->slug,
                        'display_name' => $templateRole->display_name ?? $templateRole->name,
                        'description' => $templateRole->description,
                        'is_system' => $templateRole->is_system,
                        'is_active' => $templateRole->is_active,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Copy all role_permissions from the template role
                    $permIds = DB::table('role_permissions')
                        ->where('role_id', $templateRole->id)
                        ->pluck('permission_id');

                    $pivotInserts = [];
                    foreach ($permIds as $pId) {
                        $pivotInserts[] = [
                            'role_id' => $newRoleId,
                            'permission_id' => $pId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($pivotInserts)) {
                        DB::table('role_permissions')->insert($pivotInserts);
                    }
                }
            }

            // Sync hotel_users role_id with newly created hotel-specific role
            $hotelUsers = DB::table('hotel_users')->where('hotel_id', $hotel->id)->get();
            foreach ($hotelUsers as $hu) {
                $roleSlug = strtolower(trim($hu->role ?? 'admin'));
                $targetRole = DB::table('roles')
                    ->where('hotel_id', $hotel->id)
                    ->where('slug', $roleSlug)
                    ->first();

                if ($targetRole) {
                    DB::table('hotel_users')
                        ->where('id', $hu->id)
                        ->update(['role_id' => $targetRole->id]);

                    // Also sync into user_roles with this hotel_id
                    $existsInUserRoles = DB::table('user_roles')
                        ->where('hotel_id', $hotel->id)
                        ->where('user_id', $hu->user_id)
                        ->where('role_id', $targetRole->id)
                        ->exists();

                    if (!$existsInUserRoles) {
                        DB::table('user_roles')->insert([
                            'hotel_id' => $hotel->id,
                            'user_id' => $hu->user_id,
                            'role_id' => $targetRole->id,
                            'is_primary' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hotel_users') && Schema::hasColumn('hotel_users', 'role_id')) {
            Schema::table('hotel_users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }

        if (Schema::hasTable('user_permissions') && Schema::hasColumn('user_permissions', 'hotel_id')) {
            Schema::table('user_permissions', function (Blueprint $table) {
                try { $table->dropForeign(['hotel_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex('user_permissions_hotel_user_perm_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('hotel_id');
            });
        }

        if (Schema::hasTable('user_roles') && Schema::hasColumn('user_roles', 'hotel_id')) {
            Schema::table('user_roles', function (Blueprint $table) {
                try { $table->dropForeign(['hotel_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex('user_roles_hotel_user_role_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('hotel_id');
            });
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'hotel_id')) {
            // Remove hotel-specific clones
            DB::table('roles')->whereNotNull('hotel_id')->delete();

            Schema::table('roles', function (Blueprint $table) {
                try { $table->dropForeign(['hotel_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex('roles_hotel_id_slug_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('hotel_id');
                $table->unique('slug');
            });
        }
    }
};
