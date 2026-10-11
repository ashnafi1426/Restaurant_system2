<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rooms') && !Schema::hasColumn('rooms', 'capacity')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->integer('capacity')->default(2)->after('room_type_id');
            });

            // Backfill existing rooms capacity from their room_type if available
            try {
                DB::statement("
                    UPDATE rooms r
                    LEFT JOIN room_types rt ON r.room_type_id = rt.id
                    SET r.capacity = COALESCE(rt.capacity, 2)
                ");
            } catch (\Throwable $e) {
                // Ignore if query fails on certain drivers
            }
        }

        if (Schema::hasTable('room_types') && Schema::hasColumn('room_types', 'capacity')) {
            Schema::table('room_types', function (Blueprint $table) {
                $table->integer('capacity')->default(2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'capacity')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('capacity');
            });
        }
    }
};
