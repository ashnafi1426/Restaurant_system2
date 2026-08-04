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
     * Update existing 'confirmed' reservations to 'pending' to implement new workflow:
     * Payment → pending → (receptionist confirms) → confirmed → checked_in → checked_out
     */
    public function up(): void
    {
        // Update all 'confirmed' reservations to 'pending'
        // This allows receptionist to manually confirm them
        DB::table('reservations')
            ->where('status', 'confirmed')
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'pending' back to 'confirmed'
        DB::table('reservations')
            ->where('status', 'pending')
            ->update(['status' => 'confirmed']);
    }
};
