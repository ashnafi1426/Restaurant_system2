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
     * Fix restaurant_tables status enum to match the model constants:
     * - Change 'maintenance' to 'cleaning' and 'out_of_service'
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE restaurant_tables MODIFY COLUMN status VARCHAR(100) DEFAULT 'available' COMMENT 'Current table status'");
        
        // Update any existing 'maintenance' records to 'cleaning'
        DB::table('restaurant_tables')
            ->where('status', 'maintenance')
            ->update(['status' => 'cleaning']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE restaurant_tables MODIFY COLUMN status VARCHAR(100) DEFAULT 'available' COMMENT 'Current table status'");
        
        // Convert cleaning and out_of_service back to maintenance
        DB::table('restaurant_tables')
            ->whereIn('status', ['cleaning', 'out_of_service'])
            ->update(['status' => 'maintenance']);
    }
};
