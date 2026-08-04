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
        // =========================================================
        // INVOICES TABLE
        // =========================================================
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Invoice Details
            $table->string('invoice_number')->unique()->index();
            $table->uuid('payment_id')->index();
            
            // Foreign Keys
            $table->uuid('reservation_id')->nullable()->index();
            $table->uuid('order_id')->nullable()->index();
            $table->uuid('guest_id')->index();
            
            // Invoice Status
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('pending')->index();
            
            // Amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency')->default('ETB');
            
            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign Key Constraints
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
        });

        // =========================================================
        // TRANSACTIONS TABLE
        // =========================================================
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Transaction Details
            $table->string('transaction_number')->unique()->index();
            $table->uuid('payment_id')->index();
            
            // Foreign Keys
            $table->uuid('invoice_id')->nullable()->index();
            $table->uuid('reservation_id')->nullable()->index();
            $table->uuid('order_id')->nullable()->index();
            $table->uuid('guest_id')->index();
            
            // Transaction Type
            $table->enum('type', ['payment', 'refund', 'chargeback'])->default('payment')->index();
            
            // Amounts
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('ETB');
            
            // Provider Info
            $table->string('payment_provider')->default('chapa');
            $table->string('payment_method')->nullable();
            $table->string('provider_transaction_id')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending')->index();
            
            // Additional Info
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign Key Constraints
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        // =========================================================
        // RECEIPTS TABLE
        // =========================================================
        Schema::create('receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Receipt Details
            $table->string('receipt_number')->unique()->index();
            $table->uuid('payment_id')->index();
            $table->uuid('invoice_id')->nullable()->index();
            $table->uuid('transaction_id')->nullable()->index();
            
            // Foreign Keys
            $table->uuid('reservation_id')->nullable()->index();
            $table->uuid('order_id')->nullable()->index();
            $table->uuid('guest_id')->index();
            
            // Receipt Info
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('ETB');
            $table->date('receipt_date');
            
            // PDF Storage
            $table->string('pdf_path')->nullable();
            
            // Status
            $table->enum('status', ['issued', 'sent', 'printed'])->default('issued')->index();
            
            // Email Info
            $table->string('email_to')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            
            // Additional Info
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign Key Constraints
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
        });

        // =========================================================
        // REFUNDS TABLE
        // =========================================================
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Refund Details
            $table->string('refund_number')->unique()->index();
            $table->uuid('payment_id')->index();
            $table->uuid('transaction_id')->nullable()->index();
            
            // Foreign Keys
            $table->uuid('reservation_id')->nullable()->index();
            $table->uuid('order_id')->nullable()->index();
            $table->uuid('guest_id')->index();
            
            // Refund Amount
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('ETB');
            
            // Refund Status
            $table->enum('status', ['requested', 'approved', 'rejected', 'completed', 'failed'])->default('requested')->index();
            
            // Refund Reason
            $table->text('reason');
            $table->text('rejection_reason')->nullable();
            
            // Approval Info
            $table->uuid('requested_by')->nullable(); // Cashier or Manager
            $table->uuid('approved_by')->nullable(); // Manager
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Refund Method
            $table->enum('refund_method', ['original_payment', 'cash', 'bank_transfer'])->default('original_payment');
            
            // Provider Info
            $table->string('provider_refund_id')->nullable();
            $table->json('provider_response')->nullable();
            
            // Additional Info
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign Key Constraints
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index('requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('invoices');
    }
};
