<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates waiter_notifications table for delivery assignment alerts
     */
    public function up(): void
    {
        // Only create if table doesn't exist
        if (!Schema::hasTable('waiter_notifications')) {
            Schema::create('waiter_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('waiter_id')
                    ->constrained('waiters')
                    ->onDelete('cascade');
                $table->foreignId('delivery_task_id')
                    ->nullable()
                    ->constrained('delivery_tasks')
                    ->onDelete('cascade');
                $table->string('type'); // 'assignment', 'rejection', 'reminder', etc.
                $table->string('title');
                $table->text('message');
                $table->boolean('read')->default(false);
                $table->timestamps();

                // Indices for performance
                $table->index('waiter_id', 'waiter_notifications_waiter_id_index');
                $table->index('read', 'waiter_notifications_read_index');
                $table->index('created_at', 'waiter_notifications_created_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_notifications');
    }
};
