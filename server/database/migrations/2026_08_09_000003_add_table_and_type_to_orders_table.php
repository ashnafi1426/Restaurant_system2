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
        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    if (!Schema::hasColumn('orders', 'table_id')) {
                        $table->uuid('table_id')->nullable();
                    }
                    if (!Schema::hasColumn('orders', 'order_type')) {
                        $table->string('order_type', 50)->default('room_service');
                    }
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreign('table_id')
                          ->references('id')
                          ->on('restaurant_tables')
                          ->nullOnDelete();
                    
                    $table->index('table_id');
                    $table->index('order_type');
                    $table->index(['order_type', 'status']);
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropForeign(['table_id']);
                    $table->dropColumn(['table_id', 'order_type']);
                });
            } catch (\Throwable $e) {}
        }
    }
};
