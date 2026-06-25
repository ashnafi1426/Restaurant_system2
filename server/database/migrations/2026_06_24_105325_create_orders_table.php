<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->uuid('reservation_id');
            $table->uuid('guest_id');
            $table->uuid('room_id');
            $table->dateTime('order_time')->useCurrent();
            $table->enum('status', [
                'pending',
                'preparing',
                'served',
                'cancelled'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('reservation_id');
            $table->index('status');
            $table->foreign('reservation_id')->references('id')->on('reservations');
            $table->foreign('guest_id')->references('id')->on('guests');
            $table->foreign('room_id')->references('id')->on('rooms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
