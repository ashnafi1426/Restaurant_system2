<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds last_assigned_at timestamp to track when a waiter was last assigned a delivery.
     * Used as tie-breaker when multiple waiters have the same workload.
     */
    public function up(): void
    {
        Schema::table('waiters', function (Blueprint $table) {
            $table->timestamp('last_assigned_at')->nullable()->after('current_orders');
            $table->index('last_assigned_at', 'idx_waiters_last_assigned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiters', function (Blueprint $table) {
            $table->dropIndex('idx_waiters_last_assigned');
            $table->dropColumn('last_assigned_at');
        });
    }
};
