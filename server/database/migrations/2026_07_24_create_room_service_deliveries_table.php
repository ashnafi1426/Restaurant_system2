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
        Schema::create('room_service_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('room_id');
            $table->uuid('order_id')->nullable();
            $table->uuid('delivered_by')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'delivered', 'cancelled'])->default('pending');
            $table->dateTime('scheduled_time')->nullable();
            $table->dateTime('delivered_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('delivered_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_service_deliveries');
    }
};
