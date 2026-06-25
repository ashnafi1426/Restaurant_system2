<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->uuid('order_id');

            $table->uuid('menu_item_id');

            $table->integer('quantity')
                ->default(1);

            $table->decimal('item_price_at_order', 10, 2);

            $table->decimal('total', 10, 2)
                ->storedAs('quantity * item_price_at_order');

            $table->string('notes')
                ->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('menu_item_id')
                ->references('id')
                ->on('menu_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
