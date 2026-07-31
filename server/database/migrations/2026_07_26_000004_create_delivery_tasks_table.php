<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks delivery assignments and status
     */
    public function up(): void
    {
        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable();
            $table->uuid('reservation_id')->nullable();
            $table->uuid('room_id')->nullable();
            $table->uuid('floor_id');
            $table->unsignedBigInteger('waiter_id')->nullable(); // References waiters table (auto-increment int)
            $table->uuid('assigned_by')->nullable(); // Manager ID
            $table->enum('assignment_type', ['automatic', 'manual'])->default('automatic');
            $table->enum('status', [
                'waiting_assignment',
                'assigned',
                'accepted',
                'picked_up',
                'on_delivery',
                'delivered',
                'cancelled'
            ])->default('waiting_assignment');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('on_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('floor_id')->references('id')->on('hotel_floors')->onDelete('cascade');
            $table->foreign('waiter_id')->references('id')->on('waiters')->onDelete('set null');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for queries
            $table->index(['waiter_id', 'status']);
            $table->index(['floor_id', 'status']);
            $table->index('status');
            $table->index('assignment_type');
            $table->index('assigned_at');
            $table->index('delivered_at');
            $table->unique('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_tasks');
    }
};
