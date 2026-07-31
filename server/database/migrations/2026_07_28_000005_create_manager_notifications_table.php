<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates manager_notifications table for waiter availability alerts
     */
    public function up(): void
    {
        // Only create if table doesn't exist
        if (!Schema::hasTable('manager_notifications')) {
            Schema::create('manager_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manager_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->foreignId('delivery_task_id')
                    ->nullable()
                    ->constrained('delivery_tasks')
                    ->onDelete('cascade');
                $table->string('type'); // 'waiting_assignment', 'assignment_failed', 'intervention_needed', etc.
                $table->string('title');
                $table->text('message');
                $table->boolean('read')->default(false);
                $table->timestamps();

                // Indices for performance
                $table->index('manager_id', 'manager_notifications_manager_id_index');
                $table->index('read', 'manager_notifications_read_index');
                $table->index('created_at', 'manager_notifications_created_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_notifications');
    }
};
