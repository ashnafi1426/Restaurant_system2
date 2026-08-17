<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds table section management and waiter assignments for walk-in customers
     */
    public function up(): void
    {
        // Add section to restaurant_tables
        if (!Schema::hasColumn('restaurant_tables', 'section')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->string('section')->nullable()->after('location')->comment('Restaurant section (e.g., "Main Hall", "Terrace", "VIP")');
                $table->index('section');
            });
        }

        // Create waiter_table_assignments table
        Schema::create('waiter_table_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Core assignment fields - waiter_id is bigint, not UUID
            $table->unsignedBigInteger('waiter_id');
            $table->foreignUuid('table_id')->constrained('restaurant_tables')->onDelete('cascade');
            $table->foreignUuid('shift_id')->constrained('hotel_shifts')->onDelete('cascade');
            
            // Foreign key for waiter_id
            $table->foreign('waiter_id')->references('id')->on('waiters')->onDelete('cascade');
            
            // Assignment details
            $table->date('assignment_date');
            $table->string('priority', 50)->default('primary');
            $table->string('status', 50)->default('active');
            
            // Assigned by (manager)
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('waiter_id');
            $table->index('table_id');
            $table->index('shift_id');
            $table->index('assignment_date');
            $table->index('status');
            
            // Unique constraint: one waiter per table per shift per date
            $table->unique(['table_id', 'shift_id', 'assignment_date', 'priority'], 'unique_table_shift_date_priority');
        });

        // Add table_id to delivery_tasks if not exists (for walk-in orders)
        if (!Schema::hasColumn('delivery_tasks', 'table_id')) {
            Schema::table('delivery_tasks', function (Blueprint $table) {
                $table->foreignUuid('table_id')->nullable()->after('floor_id')->constrained('restaurant_tables')->onDelete('set null');
                $table->index('table_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove table_id from delivery_tasks
        if (Schema::hasColumn('delivery_tasks', 'table_id')) {
            Schema::table('delivery_tasks', function (Blueprint $table) {
                $table->dropForeign(['table_id']);
                $table->dropColumn('table_id');
            });
        }

        // Drop waiter_table_assignments table
        Schema::dropIfExists('waiter_table_assignments');

        // Remove section from restaurant_tables
        if (Schema::hasColumn('restaurant_tables', 'section')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->dropColumn('section');
            });
        }
    }
};
