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
            // Primary Key
            $table->uuid('id')->primary();

            // Transaction Reference
            $table->string('tx_ref')->unique()->index();
            $table->string('chapa_transaction_id')->nullable()->unique();

            // Amount and Currency
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('ETB');

            // Customer Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('phone');

            // Payment Provider and Method
            $table->enum('payment_provider', ['chapa'])->default('chapa');
            $table->string('payment_method')->nullable();

            // Payment Status
            $table->enum('status', [
                'pending',
                'initialized',
                'processing',
                'paid',
                'verified',
                'failed',
                'cancelled',
                'expired',
                'refunded'
            ])->default('pending')->index();

            // URLs
            $table->text('checkout_url')->nullable();
            $table->text('callback_url')->nullable();
            $table->text('return_url')->nullable();

            // Foreign Keys - Links to Reservation/Order/Guest
            $table->uuid('reservation_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('guest_id')->nullable();

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Response Data
            $table->json('raw_response')->nullable();
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('set null');

            // Indexes for queries
            $table->index(['status', 'created_at']);
            $table->index(['payment_provider', 'created_at']);
            $table->index('created_at');
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
