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
        Schema::create('inventory_management', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('category'); // 'food', 'kitchen', 'cleaning', 'laundry', 'minibar'
            $table->string('department'); // Which department uses it
            $table->string('unit'); // 'kg', 'liters', 'pieces', 'boxes', etc.
            $table->integer('current_quantity')->default(0);
            $table->integer('minimum_quantity')->default(0);
            $table->integer('maximum_quantity')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('sku')->nullable();
            $table->string('status')->default('in_stock'); // 'in_stock', 'low_stock', 'critical', 'out_of_stock'
            $table->boolean('is_alert_enabled')->default(true);
            $table->datetime('last_restocked_at')->nullable();
            $table->datetime('last_alerted_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index(['department']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_management');
    }
};
