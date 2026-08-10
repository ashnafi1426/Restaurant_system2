<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds table_id and order_type to support restaurant table ordering.
     * - table_id: Links to restaurant_tables for walk-in orders
     * - order_type: Distinguishes between 'room_service' and 'walk_in' orders
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add table_id for restaurant table orders (nullable, not all orders have tables)
            $table->uuid('table_id')->nullable()->after('room_id');
            
            // Add order_type enum to distinguish order context
            $table->enum('order_type', ['room_service', 'walk_in'])
                  ->default('room_service')
                  ->after('table_id')
                  ->comment('Order context: room_service for hotel guests, walk_in for restaurant customers');
            
            // Add foreign key constraint for table_id
            $table->foreign('table_id')
                  ->references('id')
                  ->on('restaurant_tables')
                  ->nullOnDelete();
            
            // Add indexes for better query performance
            $table->index('table_id');
            $table->index('order_type');
            $table->index(['order_type', 'status']); // Composite index for common queries
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Removes table_id and order_type columns.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['order_type', 'status']);
            $table->dropIndex(['order_type']);
            $table->dropIndex(['table_id']);
            
            // Drop foreign key
            $table->dropForeign(['table_id']);
            
            // Drop columns
            $table->dropColumn(['table_id', 'order_type']);
        });
    }
};
