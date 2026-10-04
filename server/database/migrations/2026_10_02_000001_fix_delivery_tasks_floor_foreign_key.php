<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sync floors to hotel_floors
        if (Schema::hasTable('hotel_floors') && Schema::hasTable('floors')) {
            $floors = DB::table('floors')->get();
            foreach ($floors as $f) {
                $exists = DB::table('hotel_floors')->where('id', $f->id)->exists();
                if (!$exists) {
                    try {
                        DB::table('hotel_floors')->insert([
                            'id' => $f->id,
                            'hotel_id' => $f->hotel_id,
                            'floor_number' => $f->floor_number,
                            'name' => $f->name,
                            'description' => $f->description ?? null,
                            'total_rooms' => $f->total_rooms ?? 0,
                            'is_active' => $f->is_active ?? true,
                            'created_at' => $f->created_at ?? now(),
                            'updated_at' => $f->updated_at ?? now(),
                        ]);
                    } catch (\Throwable $e) {}
                }
            }
        }

        // 2. Drop legacy foreign key on delivery_tasks pointing to hotel_floors
        if (Schema::hasTable('delivery_tasks')) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE delivery_tasks DROP CONSTRAINT IF EXISTS delivery_tasks_floor_id_foreign');
            } else {
                try {
                    Schema::table('delivery_tasks', function (Blueprint $table) {
                        $table->dropForeign('delivery_tasks_floor_id_foreign');
                    });
                } catch (\Throwable $e) {}
            }

            // 3. Re-add foreign key pointing to floors table
            if (Schema::hasTable('floors')) {
                // Set any invalid floor_id to NULL before adding constraint
                DB::statement("UPDATE delivery_tasks SET floor_id = NULL WHERE floor_id IS NOT NULL AND floor_id NOT IN (SELECT id FROM floors)");

                try {
                    Schema::table('delivery_tasks', function (Blueprint $table) {
                        $table->foreign('floor_id')
                            ->references('id')
                            ->on('floors')
                            ->onDelete('set null');
                    });
                } catch (\Throwable $e) {
                    \Log::warning('Could not add delivery_tasks foreign key to floors: ' . $e->getMessage());
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_tasks')) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE delivery_tasks DROP CONSTRAINT IF EXISTS delivery_tasks_floor_id_foreign');
            } else {
                try {
                    Schema::table('delivery_tasks', function (Blueprint $table) {
                        $table->dropForeign(['floor_id']);
                    });
                } catch (\Throwable $e) {}
            }
        }
    }
};
