<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores daily floor assignments for waiters
     */
    public function up(): void
    {
        Schema::create('waiter_floor_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('waiter_id'); // References waiters table (auto-increment int)
            $table->uuid('floor_id');
            $table->uuid('shift_id');
            $table->date('assignment_date');
            $table->enum('status', ['assigned', 'active', 'completed', 'cancelled'])->default('assigned');
            $table->enum('priority', ['primary', 'secondary', 'backup'])->default('primary');
            $table->uuid('assigned_by')->nullable(); // Manager ID
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('waiter_id')->references('id')->on('waiters')->onDelete('cascade');
            $table->foreign('floor_id')->references('id')->on('hotel_floors')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('hotel_shifts')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for queries
            $table->index(['waiter_id', 'assignment_date']);
            $table->index(['floor_id', 'assignment_date']);
            $table->index(['shift_id', 'assignment_date']);
            $table->index('status');
            $table->index('priority');
            
            // Ensure one primary waiter per floor per shift per day
            // Using shorter name due to MySQL 64 char limit
            $table->unique(['floor_id', 'shift_id', 'assignment_date', 'priority'], 'wfa_floor_shift_date_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_floor_assignments');
    }
};
