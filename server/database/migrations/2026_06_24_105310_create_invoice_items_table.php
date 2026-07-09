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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->string('description');
            $table->integer('quantity') ->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2)->storedAs('quantity * unit_price');
            $table->enum('source_type', [
                'room_charge',
                'restaurant_charge',
                'service_charge',
                'tax',
                'other'
            ]);
            $table->uuid('source_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('invoice_id')->references('id') ->on('invoices')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
