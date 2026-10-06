<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds composite indexes for Waiter Dashboard optimization
     * Expected performance improvement: 30-50% on filtered queries
     */
    public function up(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            // Composite index for waiter + status + assigned_at (getTodayStats, getQuickStats)
            if (!$this->indexExists('delivery_tasks', 'delivery_tasks_waiter_status_assigned_idx')) {
                $table->index(['waiter_id', 'status', 'assigned_at'], 'delivery_tasks_waiter_status_assigned_idx');
            }

            // Composite index for hotel + status + created_at (dashboard filtering)
            if (!$this->indexExists('delivery_tasks', 'delivery_tasks_hotel_status_created_idx')) {
                $table->index(['hotel_id', 'status', 'created_at'], 'delivery_tasks_hotel_status_created_idx');
            }

            // Composite index for order_id + status (order-based lookups)
            if (!$this->indexExists('delivery_tasks', 'delivery_tasks_order_status_idx')) {
                $table->index(['order_id', 'status'], 'delivery_tasks_order_status_idx');
            }
        });

        Schema::table('waiter_performance', function (Blueprint $table) {
            // Composite index for waiter + metric_date (performance queries)
            if (!$this->indexExists('waiter_performance', 'waiter_performance_waiter_date_idx')) {
                $table->index(['waiter_id', 'metric_date'], 'waiter_performance_waiter_date_idx');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // Composite index for hotel + status + updated_at (kitchen ready orders)
            if (!$this->indexExists('orders', 'orders_hotel_status_updated_idx')) {
                $table->index(['hotel_id', 'status', 'updated_at'], 'orders_hotel_status_updated_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropIndexIfExists('delivery_tasks_waiter_status_assigned_idx');
            $table->dropIndexIfExists('delivery_tasks_hotel_status_created_idx');
            $table->dropIndexIfExists('delivery_tasks_order_status_idx');
        });

        Schema::table('waiter_performance', function (Blueprint $table) {
            $table->dropIndexIfExists('waiter_performance_waiter_date_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndexIfExists('orders_hotel_status_updated_idx');
        });
    }

    /**
     * Check if index exists (helper function)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $indexes = $connection->select(
            "SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$databaseName, $table, $indexName]
        );
        return count($indexes) > 0;
    }
};
