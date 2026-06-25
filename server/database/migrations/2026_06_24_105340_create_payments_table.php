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
        Schema::create('payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('transaction_reference')
          ->unique();
    $table->uuid('invoice_id');
    $table->uuid('guest_id');
    $table->decimal('amount',10,2);
    $table->enum('payment_method',['cash','card','bank_transfer','online' ]);
    $table->enum('status',['pending','completed','failed','refunded' ])->default('pending');
    $table->dateTime('payment_date')
          ->useCurrent();
    $table->uuid('processed_by')
          ->nullable();
    $table->string('refund_reference')
          ->nullable();
    $table->timestamps();
    $table->index('invoice_id');
    $table->index('guest_id');
    $table->index('status');
    $table->foreign('invoice_id')
          ->references('id')
          ->on('invoices');
    $table->foreign('guest_id')
          ->references('id')
          ->on('guests');
    $table->foreign('processed_by')
          ->references('id')
          ->on('users')
          ->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
