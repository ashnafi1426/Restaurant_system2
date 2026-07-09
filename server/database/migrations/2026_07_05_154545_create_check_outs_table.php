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
        Schema::create('check_outs', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->uuid('check_in_id')->unique();

            $table->uuid('reservation_id');

            $table->uuid('guest_id');

            $table->uuid('room_id');

            /*
            |--------------------------------------------------------------------------
            | Checkout Information
            |--------------------------------------------------------------------------
            */

            $table->dateTime('checked_out_at');

            /*
            |--------------------------------------------------------------------------
            | Billing
            |--------------------------------------------------------------------------
            */

            $table->decimal('room_charge', 12, 2)->default(0);

            $table->decimal('restaurant_charge', 12, 2)->default(0);

            $table->decimal('service_charge', 12, 2)->default(0);

            $table->decimal('tax', 12, 2)->default(0);

            $table->decimal('discount', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2);

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->enum('payment_method', [
                'cash',
                'card',
                'bank_transfer',
                'mobile_money'
            ]);

            $table->enum('payment_status', [
                'pending',
                'paid'
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text('remarks')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('check_in_id')
                ->references('id')
                ->on('check_ins')
                ->cascadeOnDelete();

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_outs');
    }
};