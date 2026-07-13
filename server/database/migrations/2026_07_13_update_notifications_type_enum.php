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
        // For MySQL, we need to modify the ENUM to include the new types
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('booking', 'check_in', 'check_out', 'cancellation', 'system', 'order_created', 'order_preparing', 'order_ready', 'order_served') DEFAULT 'booking'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM values
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('booking', 'check_in', 'check_out', 'cancellation', 'system') DEFAULT 'booking'");
    }
};
