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
     * Makes room_id, guest_id, and reservation_id nullable to support walk-in orders
     * that don't have an associated room/reservation/guest.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['room_id']);
            $table->dropForeign(['guest_id']);
            $table->dropForeign(['reservation_id']);
        });

        // Alter columns to be nullable using raw SQL
        // Laravel's change() method doesn't always work reliably with UUIDs
        DB::statement('ALTER TABLE orders MODIFY COLUMN room_id CHAR(36) NULL');
        DB::statement('ALTER TABLE orders MODIFY COLUMN guest_id CHAR(36) NULL');
        DB::statement('ALTER TABLE orders MODIFY COLUMN reservation_id CHAR(36) NULL');

        Schema::table('orders', function (Blueprint $table) {
            // Re-add foreign keys with nullable and cascade on delete
            $table->foreign('room_id')
                  ->references('id')
                  ->on('rooms')
                  ->nullOnDelete();
            
            $table->foreign('guest_id')
                  ->references('id')
                  ->on('guests')
                  ->nullOnDelete();
            
            $table->foreign('reservation_id')
                  ->references('id')
                  ->on('reservations')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     * 
     * WARNING: Rolling back this migration may FAIL if any orders exist with NULL
     * room_id, guest_id, or reservation_id. Those records must be deleted first.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop nullable foreign keys
            $table->dropForeign(['room_id']);
            $table->dropForeign(['guest_id']);
            $table->dropForeign(['reservation_id']);
        });

        // Revert columns to NOT NULL
        // WARNING: This will fail if any orders have NULL values
        DB::statement('ALTER TABLE orders MODIFY COLUMN room_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY COLUMN guest_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY COLUMN reservation_id CHAR(36) NOT NULL');

        Schema::table('orders', function (Blueprint $table) {
            // Re-add original non-nullable foreign keys
            $table->foreign('room_id')
                  ->references('id')
                  ->on('rooms')
                  ->onDelete('cascade');
            
            $table->foreign('guest_id')
                  ->references('id')
                  ->on('guests')
                  ->onDelete('cascade');
            
            $table->foreign('reservation_id')
                  ->references('id')
                  ->on('reservations')
                  ->onDelete('cascade');
        });
    }
};
