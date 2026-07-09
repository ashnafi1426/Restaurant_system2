<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')
                ->unique();
            $table->uuid('reservation_id')
                ->unique();
            $table->uuid('guest_id');
            $table->uuid('room_id');
            $table->dateTime('issue_date')
                ->useCurrent();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('service_charge', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)
                ->default(0);
            $table->decimal('balance_due', 10, 2)
                ->storedAs('total_amount - amount_paid');
            $table->enum('status', [
                'unpaid',
                'partially_paid',
                'paid',
                'refunded'
            ])->default('unpaid');
            $table->timestamps();
            $table->foreign('reservation_id')
                ->references('id')
                ->on('reservations');
            $table->foreign('guest_id')
                ->references('id')
                ->on('guests');
            $table->foreign('room_id')
                ->references('id')
                ->on('rooms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
