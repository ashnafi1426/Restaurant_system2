<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure invoice_id is nullable if present
        if (Schema::hasColumn('payments', 'invoice_id')) {
            try {
                DB::statement("ALTER TABLE `payments` MODIFY `invoice_id` CHAR(36) NULL");
            } catch (\Throwable $e) {
                // Ignore if already nullable or driver specific
            }
        }

        // 2. Add any missing columns
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'tx_ref')) {
                $table->string('tx_ref')->nullable()->index();
            }
            if (!Schema::hasColumn('payments', 'chapa_transaction_id')) {
                $table->string('chapa_transaction_id')->nullable()->index();
            }
            if (!Schema::hasColumn('payments', 'currency')) {
                $table->string('currency', 10)->default('ETB');
            }
            if (!Schema::hasColumn('payments', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (!Schema::hasColumn('payments', 'last_name')) {
                $table->string('last_name')->nullable();
            }
            if (!Schema::hasColumn('payments', 'email')) {
                $table->string('email')->nullable()->index();
            }
            if (!Schema::hasColumn('payments', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('payments', 'payment_provider')) {
                $table->string('payment_provider', 50)->default('chapa');
            }
            if (!Schema::hasColumn('payments', 'payment_status')) {
                $table->string('payment_status', 50)->nullable();
            }
            if (!Schema::hasColumn('payments', 'checkout_url')) {
                $table->text('checkout_url')->nullable();
            }
            if (!Schema::hasColumn('payments', 'callback_url')) {
                $table->text('callback_url')->nullable();
            }
            if (!Schema::hasColumn('payments', 'return_url')) {
                $table->text('return_url')->nullable();
            }
            if (!Schema::hasColumn('payments', 'reservation_id')) {
                $table->uuid('reservation_id')->nullable();
            }
            if (!Schema::hasColumn('payments', 'order_id')) {
                $table->uuid('order_id')->nullable();
            }
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'refund_reason')) {
                $table->string('refund_reason')->nullable();
            }
            if (!Schema::hasColumn('payments', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('payments', 'raw_response')) {
                $table->json('raw_response')->nullable();
            }
            if (!Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op or column drop
    }
};
