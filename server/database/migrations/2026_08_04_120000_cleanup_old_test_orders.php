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
        // Delete all orders with 'ready' status that are older than 2 days
        // This removes old test data that's cluttering the system
        DB::table('orders')
            ->where('status', 'ready')
            ->where('created_at', '<', now()->subDays(2))
            ->delete();

        // Also delete old 'served' orders older than 7 days
        DB::table('orders')
            ->where('status', 'served')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        // Optional: Reset auto-increment if you want clean IDs
        // This is commented out since we're using UUIDs
        // DB::statement('ALTER TABLE orders AUTO_INCREMENT = 1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as it deletes data
        // We cannot restore deleted test data
    }
};
