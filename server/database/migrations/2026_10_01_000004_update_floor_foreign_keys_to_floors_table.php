<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure all hotel_floors exist in floors and vice-versa
        if (Schema::hasTable('hotel_floors') && Schema::hasTable('floors')) {
            $defaultHotelId = DB::table('hotels')->value('id');

            $hotelFloors = DB::table('hotel_floors')->get();
            foreach ($hotelFloors as $hf) {
                $exists = DB::table('floors')->where('id', $hf->id)->exists();
                if (!$exists) {
                    DB::table('floors')->insert([
                        'id' => $hf->id,
                        'hotel_id' => $hf->hotel_id ?? $defaultHotelId,
                        'floor_number' => $hf->floor_number,
                        'name' => $hf->name,
                        'description' => $hf->description ?? null,
                        'total_rooms' => $hf->total_rooms ?? 0,
                        'is_active' => $hf->is_active ?? true,
                        'created_at' => $hf->created_at ?? now(),
                        'updated_at' => $hf->updated_at ?? now(),
                    ]);
                }
            }

            $floors = DB::table('floors')->get();
            foreach ($floors as $f) {
                $exists = DB::table('hotel_floors')->where('id', $f->id)->exists();
                if (!$exists) {
                    try {
                        DB::table('hotel_floors')->insert([
                            'id' => $f->id,
                            'hotel_id' => $f->hotel_id ?? $defaultHotelId,
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

        // 2. Update rooms.floor_id foreign key to point to floors table
        if (Schema::hasTable('rooms') && Schema::hasTable('floors')) {
            Schema::table('rooms', function (Blueprint $table) {
                try {
                    $table->dropForeign('rooms_floor_id_foreign');
                } catch (\Throwable $e) {}
            });

            // Clean any orphaned floor_ids before adding FK
            DB::statement("UPDATE `rooms` SET `floor_id` = NULL WHERE `floor_id` IS NOT NULL AND `floor_id` NOT IN (SELECT `id` FROM `floors`)");

            try {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->foreign('floor_id')
                        ->references('id')
                        ->on('floors')
                        ->onDelete('set null');
                });
            } catch (\Throwable $e) {
                \Log::warning('Could not add rooms foreign key to floors: ' . $e->getMessage());
            }
        }

        // 3. Update waiter_floor_assignments.floor_id foreign key if needed
        if (Schema::hasTable('waiter_floor_assignments') && Schema::hasTable('floors')) {
            Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                try {
                    $table->dropForeign('waiter_floor_assignments_floor_id_foreign');
                } catch (\Throwable $e) {}
            });

            try {
                Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                    $table->foreign('floor_id')
                        ->references('id')
                        ->on('floors')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                \Log::warning('Could not add waiter_floor_assignments foreign key to floors: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
    }
};
