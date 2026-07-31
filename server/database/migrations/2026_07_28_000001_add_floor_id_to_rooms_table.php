<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds floor_id field to rooms table with foreign key to hotel_floors
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Add floor_id column if it doesn't exist
            if (!Schema::hasColumn('rooms', 'floor_id')) {
                // Use string UUID type to match hotel_floors id type, then constrain as foreign key
                $table->uuid('floor_id')->nullable();
                $table->foreign('floor_id')
                    ->references('id')
                    ->on('hotel_floors')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['floor_id']);
            // Then drop the column
            $table->dropColumn('floor_id');
        });
    }
};
