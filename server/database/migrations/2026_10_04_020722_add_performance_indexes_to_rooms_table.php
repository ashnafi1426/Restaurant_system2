<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            $table->index('status', 'rooms_status_index');
            $table->index('is_active', 'rooms_is_active_index');
            $table->index('room_type_id', 'rooms_room_type_id_index');
            
            // Composite index for common query patterns (hotel + status + active)
            $table->index(['hotel_id', 'status', 'is_active'], 'rooms_hotel_status_active_index');
            
            // Index for room number searches
            $table->index('room_number', 'rooms_room_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_status_index');
            $table->dropIndex('rooms_is_active_index');
            $table->dropIndex('rooms_room_type_id_index');
            $table->dropIndex('rooms_hotel_status_active_index');
            $table->dropIndex('rooms_room_number_index');
        });
    }
};