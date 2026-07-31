<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds waiter management fields: section, shift, experience_level, employee_number
     */
    public function up(): void
    {
        Schema::table('waiters', function (Blueprint $table) {
            // Add section field for waiter assignment
            if (!Schema::hasColumn('waiters', 'section')) {
                $table->string('section', 100)->nullable()->after('phone');
            }
            
            // Add shift field for scheduling
            if (!Schema::hasColumn('waiters', 'shift')) {
                $table->enum('shift', ['morning', 'afternoon', 'evening', 'night'])
                    ->nullable()
                    ->after('section');
            }
            
            // Add experience level for qualification
            if (!Schema::hasColumn('waiters', 'experience_level')) {
                $table->enum('experience_level', ['junior', 'senior', 'head'])
                    ->default('junior')
                    ->after('shift');
            }
            
            // Add employee number for identification
            if (!Schema::hasColumn('waiters', 'employee_number')) {
                $table->string('employee_number', 50)->nullable()->unique()->after('experience_level');
            }
            
            // Add indexes for better query performance
            if (!Schema::hasIndex('waiters', 'waiters_section_index')) {
                $table->index('section');
            }
            if (!Schema::hasIndex('waiters', 'waiters_shift_index')) {
                $table->index('shift');
            }
            if (!Schema::hasIndex('waiters', 'waiters_experience_level_index')) {
                $table->index('experience_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiters', function (Blueprint $table) {
            $table->dropIndex('waiters_section_index');
            $table->dropIndex('waiters_shift_index');
            $table->dropIndex('waiters_experience_level_index');
            $table->dropColumn(['section', 'shift', 'experience_level', 'employee_number']);
        });
    }
};
