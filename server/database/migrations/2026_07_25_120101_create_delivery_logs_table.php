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
        if (!Schema::hasTable('delivery_logs')) {
            Schema::create('delivery_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('waiter_id')->index();
                $table->uuid('order_id');
                $table->uuid('room_id')->nullable();
                
                $table->string('action'); // assigned, accepted, rejected, picked_up, started_delivery, delivered, failed
                $table->text('description')->nullable();
                $table->json('location')->nullable(); // {lat: 0, lng: 0, accuracy: 0}
                
                $table->timestamps();

                $table->foreign('waiter_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');

                // Indexes
                $table->index(['waiter_id', 'created_at']);
                $table->index(['order_id']);
                $table->index(['action']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_logs');
    }
};
