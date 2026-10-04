<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add indexes on columns used in WHERE clauses and ORDER BY
     * to optimize room query performance.
     */
    public function up(): void
    {
        // Use raw SQL to check and create indexes for PostgreSQL
        $indexes = [
            'rooms_room_type_id_index' => 'CREATE INDEX IF NOT EXISTS rooms_room_type_id_index ON rooms(room_type_id)',
            'rooms_is_active_index' => 'CREATE INDEX IF NOT EXISTS rooms_is_active_index ON rooms(is_active)',
            'rooms_status_is_active_index' => 'CREATE INDEX IF NOT EXISTS rooms_status_is_active_index ON rooms(status, is_active)',
            'rooms_room_type_status_index' => 'CREATE INDEX IF NOT EXISTS rooms_room_type_status_index ON rooms(room_type_id, status)',
        ];

        foreach ($indexes as $name => $sql) {
            DB::statement($sql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'rooms_room_type_id_index',
            'rooms_is_active_index',
            'rooms_status_is_active_index',
            'rooms_room_type_status_index',
        ];

        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
