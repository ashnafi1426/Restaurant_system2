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

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('menu_item_id')
                ->constrained();

            /*
            |--------------------------------------------------------------------------
            | Order Item
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('quantity')
                ->default(1);

            /*
            |--------------------------------------------------------------------------
            | Snapshot Price
            |--------------------------------------------------------------------------
            | Save the menu price at the time of ordering.
            | Future menu price changes won't affect old orders.
            |--------------------------------------------------------------------------
            */

            $table->decimal('item_price_at_order', 10, 2);

            /*
            |--------------------------------------------------------------------------
            | Line Total
            |--------------------------------------------------------------------------
            */

            $table->decimal('line_total', 10, 2);

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->string('notes')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('order_id');

            $table->index('menu_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};