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
        if (Schema::hasTable('restaurant_tables')) {
            try {
                DB::statement("ALTER TABLE restaurant_tables MODIFY COLUMN status VARCHAR(100) DEFAULT 'available'");
            } catch (\Throwable $e) {}
            
            try {
                DB::table('restaurant_tables')
                    ->where('status', 'maintenance')
                    ->update(['status' => 'cleaning']);
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('restaurant_tables')) {
            try {
                DB::statement("ALTER TABLE restaurant_tables MODIFY COLUMN status VARCHAR(100) DEFAULT 'available'");
            } catch (\Throwable $e) {}
            
            try {
                DB::table('restaurant_tables')
                    ->whereIn('status', ['cleaning', 'out_of_service'])
                    ->update(['status' => 'maintenance']);
            } catch (\Throwable $e) {}
        }
    }
};
