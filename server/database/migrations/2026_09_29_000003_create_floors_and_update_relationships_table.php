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
        // 1. Create floors table
        if (!Schema::hasTable('floors')) {
            Schema::create('floors', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('hotel_id')->nullable();
                $table->string('name');
                $table->integer('floor_number');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['hotel_id', 'is_active']);
                $table->index(['hotel_id', 'floor_number']);
            });

            // Copy existing floors from hotel_floors if it exists
            if (Schema::hasTable('hotel_floors')) {
                $defaultHotelId = DB::table('hotels')->value('id');
                $existing = DB::table('hotel_floors')->get();
                foreach ($existing as $hf) {
                    DB::table('floors')->insert([
                        'id' => $hf->id,
                        'hotel_id' => $hf->hotel_id ?? $defaultHotelId,
                        'name' => $hf->name,
                        'floor_number' => $hf->floor_number,
                        'is_active' => $hf->is_active ?? true,
                        'created_at' => $hf->created_at ?? now(),
                        'updated_at' => $hf->updated_at ?? now(),
                    ]);
                }
            }
        }

        // 2. Update rooms table: ensure floor_id exists
        if (Schema::hasTable('rooms')) {
            if (!Schema::hasColumn('rooms', 'floor_id')) {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->uuid('floor_id')->nullable()->after('room_type_id');
                });
            }

            // Backfill room floor_id from floors table where floor number matches
            if (Schema::hasTable('floors')) {
                $roomsWithoutFloor = DB::table('rooms')->whereNull('floor_id')->get();
                foreach ($roomsWithoutFloor as $rm) {
                    if (!empty($rm->floor)) {
                        $flr = DB::table('floors')
                            ->where('floor_number', $rm->floor)
                            ->when(!empty($rm->hotel_id), fn($q) => $q->where('hotel_id', $rm->hotel_id))
                            ->first();
                        if ($flr) {
                            DB::table('rooms')->where('id', $rm->id)->update(['floor_id' => $flr->id]);
                        }
                    }
                }
            }
        }

        // 3. Create or update waiter_floor_assignments table
        if (!Schema::hasTable('waiter_floor_assignments')) {
            Schema::create('waiter_floor_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('hotel_id')->nullable();
                $table->uuid('floor_id');
                $table->unsignedBigInteger('waiter_id');
                $table->boolean('is_active')->default(true);
                $table->timestamp('assigned_at')->nullable()->useCurrent();
                $table->timestamps();

                $table->index(['hotel_id', 'is_active']);
                $table->index(['floor_id', 'is_active']);
                $table->index(['waiter_id', 'is_active']);
            });
        } else {
            // Add missing columns if waiter_floor_assignments exists
            if (!Schema::hasColumn('waiter_floor_assignments', 'hotel_id')) {
                Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                    $table->uuid('hotel_id')->nullable()->after('id');
                });
            }

            if (!Schema::hasColumn('waiter_floor_assignments', 'is_active')) {
                Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('waiter_id');
                });

                if (Schema::hasColumn('waiter_floor_assignments', 'status')) {
                    DB::table('waiter_floor_assignments')
                        ->where('status', 'active')
                        ->update(['is_active' => true]);
                    DB::table('waiter_floor_assignments')
                        ->where('status', '!=', 'active')
                        ->update(['is_active' => false]);
                }
            }

            if (!Schema::hasColumn('waiter_floor_assignments', 'assigned_at')) {
                Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                    $table->timestamp('assigned_at')->nullable()->after('is_active');
                });
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::statement('UPDATE "waiter_floor_assignments" SET "assigned_at" = "created_at" WHERE "assigned_at" IS NULL');
                } else {
                    DB::statement("UPDATE `waiter_floor_assignments` SET `assigned_at` = `created_at` WHERE `assigned_at` IS NULL");
                }
            }

            // Drop restrictive legacy indexes
            if (DB::connection()->getDriverName() === 'pgsql') {
                try {
                    DB::statement('DROP INDEX IF EXISTS wfa_floor_shift_date_priority');
                } catch (\Throwable $e) {}
                try {
                    DB::statement('DROP INDEX IF EXISTS wfa_waiter_floor_shift_date_unique');
                } catch (\Throwable $e) {}
                try {
                    DB::statement('
                        UPDATE waiter_floor_assignments wfa
                        SET hotel_id = w.hotel_id
                        FROM waiters w
                        WHERE wfa.waiter_id = w.id
                        AND wfa.hotel_id IS NULL AND w.hotel_id IS NOT NULL
                    ');
                } catch (\Throwable $e) {}
            } else {
                try {
                    DB::statement("ALTER TABLE `waiter_floor_assignments` DROP INDEX `wfa_floor_shift_date_priority`");
                } catch (\Throwable $e) {}
                try {
                    DB::statement("ALTER TABLE `waiter_floor_assignments` DROP INDEX `wfa_waiter_floor_shift_date_unique`");
                } catch (\Throwable $e) {}

                // Backfill hotel_id from waiters
                try {
                    DB::statement("
                        UPDATE `waiter_floor_assignments` wfa
                        JOIN `waiters` w ON wfa.waiter_id = w.id
                        SET wfa.hotel_id = w.hotel_id
                        WHERE wfa.hotel_id IS NULL AND w.hotel_id IS NOT NULL
                    ");
                } catch (\Throwable $e) {}
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op to prevent data loss
    }
};
