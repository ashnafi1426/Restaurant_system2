<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds composite and column indexes for Admin Dashboard query optimization.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!$this->indexExists('reservations', 'reservations_created_at_idx')) {
                $table->index('created_at', 'reservations_created_at_idx');
            }
            if (!$this->indexExists('reservations', 'reservations_hotel_created_idx')) {
                $table->index(['hotel_id', 'created_at'], 'reservations_hotel_created_idx');
            }
            if (!$this->indexExists('reservations', 'reservations_hotel_status_idx')) {
                $table->index(['hotel_id', 'status'], 'reservations_hotel_status_idx');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!$this->indexExists('payments', 'payments_created_at_idx')) {
                $table->index('created_at', 'payments_created_at_idx');
            }
            if (!$this->indexExists('payments', 'payments_status_created_idx')) {
                $table->index(['status', 'created_at'], 'payments_status_created_idx');
            }
            if (Schema::hasColumn('payments', 'hotel_id') && !$this->indexExists('payments', 'payments_hotel_status_created_idx')) {
                $table->index(['hotel_id', 'status', 'created_at'], 'payments_hotel_status_created_idx');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'orders_created_at_idx')) {
                $table->index('created_at', 'orders_created_at_idx');
            }
            if (!$this->indexExists('orders', 'orders_hotel_created_idx')) {
                $table->index(['hotel_id', 'created_at'], 'orders_hotel_created_idx');
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            if (!$this->indexExists('rooms', 'rooms_hotel_status_idx')) {
                $table->index(['hotel_id', 'status'], 'rooms_hotel_status_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndexIfExists('reservations_created_at_idx');
            $table->dropIndexIfExists('reservations_hotel_created_idx');
            $table->dropIndexIfExists('reservations_hotel_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndexIfExists('payments_created_at_idx');
            $table->dropIndexIfExists('payments_status_created_idx');
            $table->dropIndexIfExists('payments_hotel_status_created_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndexIfExists('orders_created_at_idx');
            $table->dropIndexIfExists('orders_hotel_created_idx');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndexIfExists('rooms_hotel_status_idx');
        });
    }

    /**
     * Check if index exists helper
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
