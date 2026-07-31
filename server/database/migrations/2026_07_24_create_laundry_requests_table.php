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
        Schema::create('laundry_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('room_id');
            $table->uuid('guest_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->json('items')->nullable();
            $table->dateTime('requested_time')->nullable();
            $table->dateTime('pickup_time')->nullable();
            $table->dateTime('delivery_time')->nullable();
            $table->decimal('cost', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundry_requests');
    }
};
