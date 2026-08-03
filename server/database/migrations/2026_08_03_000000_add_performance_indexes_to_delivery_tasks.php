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
        Schema::table('delivery_tasks', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            $table->index(['waiter_id', 'status'], 'idx_waiter_status');
            $table->index(['waiter_id', 'assigned_at'], 'idx_waiter_assigned');
            $table->index(['waiter_id', 'delivered_at'], 'idx_waiter_delivered');
            $table->index(['status', 'assigned_at'], 'idx_status_assigned');
        });

        Schema::table('waiter_performance', function (Blueprint $table) {
            // Add indexes for performance queries
            $table->index(['waiter_id', 'metric_date'], 'idx_waiter_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_waiter_status');
            $table->dropIndex('idx_waiter_assigned');
            $table->dropIndex('idx_waiter_delivered');
            $table->dropIndex('idx_status_assigned');
        });

        Schema::table('waiter_performance', function (Blueprint $table) {
            $table->dropIndex('idx_waiter_date');
        });
    }
};
