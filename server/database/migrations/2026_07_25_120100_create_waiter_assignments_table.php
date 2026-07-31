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
        if (!Schema::hasTable('waiter_assignments')) {
            Schema::create('waiter_assignments', function (Blueprint $table) {
                $table->id();
                $table->uuid('waiter_id')->index();
                $table->uuid('order_id');  // orders table uses UUID primary key
                $table->uuid('assigned_by')->nullable(); // Manager who assigned
                $table->dateTime('assigned_at')->nullable();
                $table->dateTime('accepted_at')->nullable();
                $table->dateTime('rejected_at')->nullable();
                $table->dateTime('picked_up_at')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->dateTime('failed_at')->nullable();
                
                $table->string('status')->default('pending'); // pending, accepted, rejected, ready, picked_up, on_delivery, delivered, failed, cancelled
                $table->string('rejection_reason')->nullable();
                $table->string('failure_reason')->nullable(); // guest_unavailable, wrong_room, guest_refused, order_damaged, other
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('waiter_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

                // Indexes
                $table->index(['waiter_id', 'status']);
                $table->index(['status', 'created_at']);
                $table->index(['order_id']);
                $table->unique(['waiter_id', 'order_id']); // One assignment per waiter per order
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_assignments');
    }
};
