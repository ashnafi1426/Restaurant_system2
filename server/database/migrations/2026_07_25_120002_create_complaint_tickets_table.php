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
        Schema::create('complaint_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->uuid('guest_id')->nullable();
            $table->uuid('manager_id')->nullable(); // Who's handling it
            $table->string('type'); // 'food', 'room', 'housekeeping', 'laundry', 'reception', 'restaurant', 'staff', 'maintenance', 'payment'
            $table->string('department')->nullable(); // Target department
            $table->string('severity')->default('normal'); // 'low', 'normal', 'high', 'critical'
            $table->string('status')->default('open'); // 'open', 'assigned', 'in_progress', 'escalated', 'resolved', 'closed'
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->uuid('assigned_to')->nullable(); // Staff member
            $table->datetime('assigned_at')->nullable();
            $table->datetime('escalated_at')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->integer('satisfaction_rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->foreign('guest_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['ticket_number']);
            $table->index(['status', 'severity']);
            $table->index(['manager_id', 'created_at']);
            $table->index(['type', 'department']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_tickets');
    }
};
