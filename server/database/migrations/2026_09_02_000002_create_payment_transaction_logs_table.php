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
        Schema::create('payment_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('payment_id')->index();
            $table->string('tx_ref')->index();
            $table->uuid('hotel_id')->index();
            $table->string('status'); // pending, completed, failed, refunded, etc.
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->json('details')->nullable(); // Additional transaction details
            $table->uuid('logged_by')->nullable(); // User who triggered the log
            $table->dateTime('logged_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('cascade');

            $table->foreign('hotel_id')
                ->references('id')
                ->on('hotels')
                ->onDelete('cascade');

            // Indexes for queries
            $table->index(['payment_id', 'status']);
            $table->index(['hotel_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transaction_logs');
    }
};
