<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This updates the existing waiters table with additional fields
     */
    public function up(): void
    {
        Schema::table('waiters', function (Blueprint $table) {
            // Ensure status column exists
            if (!Schema::hasColumn('waiters', 'status')) {
                $table->enum('status', ['active', 'inactive', 'on_break'])->default('active');
            }
            
            // Add phone first if not exists
            if (!Schema::hasColumn('waiters', 'phone')) {
                $table->string('phone')->nullable();
            }
            
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('waiters', 'employment_type')) {
                $table->enum('employment_type', ['full_time', 'part_time', 'contract'])
                    ->default('full_time');
            }
            
            if (!Schema::hasColumn('waiters', 'hire_date')) {
                $table->date('hire_date')->nullable();
            }
            
            if (!Schema::hasColumn('waiters', 'availability')) {
                $table->enum('availability', ['available', 'busy', 'break', 'offline'])
                    ->default('offline');
            }
            
            if (!Schema::hasColumn('waiters', 'current_orders')) {
                $table->integer('current_orders')->default(0);
            }
            
            if (!Schema::hasColumn('waiters', 'maximum_orders')) {
                $table->integer('maximum_orders')->default(5);
            }
            
            if (!Schema::hasColumn('waiters', 'profile_photo')) {
                $table->string('profile_photo')->nullable();
            }
        });
        
        // Add indexes separately to avoid issues with non-existent columns
        if (Schema::hasColumn('waiters', 'status')) {
            Schema::table('waiters', function (Blueprint $table) {
                if (!Schema::hasIndex('waiters', 'waiters_status_index')) {
                    $table->index('status', 'waiters_status_index');
                }
            });
        }
        
        if (Schema::hasColumn('waiters', 'availability')) {
            Schema::table('waiters', function (Blueprint $table) {
                if (!Schema::hasIndex('waiters', 'waiters_availability_index')) {
                    $table->index('availability', 'waiters_availability_index');
                }
            });
        }
        
        if (Schema::hasColumn('waiters', 'employment_type')) {
            Schema::table('waiters', function (Blueprint $table) {
                if (!Schema::hasIndex('waiters', 'waiters_employment_type_index')) {
                    $table->index('employment_type', 'waiters_employment_type_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiters', function (Blueprint $table) {
            $columns = [
                'employment_type',
                'hire_date',
                'availability',
                'current_orders',
                'maximum_orders',
                'profile_photo'
            ];
            
            // Only drop columns that actually exist
            foreach ($columns as $column) {
                if (Schema::hasColumn('waiters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
