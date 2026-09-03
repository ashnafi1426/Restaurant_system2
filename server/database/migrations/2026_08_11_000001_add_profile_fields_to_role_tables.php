<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add profile fields to administrators table
        if (Schema::hasTable('administrators')) {
            try {
                Schema::table('administrators', function (Blueprint $table) {
                    if (!Schema::hasColumn('administrators', 'employee_code')) {
                        $table->string('employee_code')->nullable()->unique();
                    }
                    if (!Schema::hasColumn('administrators', 'department')) {
                        $table->string('department')->nullable();
                    }
                    if (!Schema::hasColumn('administrators', 'bio')) {
                        $table->text('bio')->nullable();
                    }
                    if (!Schema::hasColumn('administrators', 'profile_photo')) {
                        $table->string('profile_photo')->nullable();
                    }
                    if (!Schema::hasColumn('administrators', 'hire_date')) {
                        $table->date('hire_date')->nullable();
                    }
                    if (!Schema::hasColumn('administrators', 'status')) {
                        $table->string('status', 50)->default('active');
                    }
                });
            } catch (\Throwable $e) {}
        }

        // Add profile fields to receptionists table
        if (Schema::hasTable('receptionists')) {
            try {
                Schema::table('receptionists', function (Blueprint $table) {
                    if (!Schema::hasColumn('receptionists', 'employee_code')) {
                        $table->string('employee_code')->nullable()->unique();
                    }
                    if (!Schema::hasColumn('receptionists', 'shift')) {
                        $table->string('shift')->nullable();
                    }
                    if (!Schema::hasColumn('receptionists', 'bio')) {
                        $table->text('bio')->nullable();
                    }
                    if (!Schema::hasColumn('receptionists', 'profile_photo')) {
                        $table->string('profile_photo')->nullable();
                    }
                    if (!Schema::hasColumn('receptionists', 'hire_date')) {
                        $table->date('hire_date')->nullable();
                    }
                    if (!Schema::hasColumn('receptionists', 'status')) {
                        $table->string('status', 50)->default('active');
                    }
                    if (!Schema::hasColumn('receptionists', 'desk_number')) {
                        $table->string('desk_number')->nullable();
                    }
                });
            } catch (\Throwable $e) {}
        }

        // Add profile fields to cashiers table
        if (Schema::hasTable('cashiers')) {
            try {
                Schema::table('cashiers', function (Blueprint $table) {
                    if (!Schema::hasColumn('cashiers', 'employee_code')) {
                        $table->string('employee_code')->nullable()->unique();
                    }
                    if (!Schema::hasColumn('cashiers', 'shift')) {
                        $table->string('shift')->nullable();
                    }
                    if (!Schema::hasColumn('cashiers', 'bio')) {
                        $table->text('bio')->nullable();
                    }
                    if (!Schema::hasColumn('cashiers', 'profile_photo')) {
                        $table->string('profile_photo')->nullable();
                    }
                    if (!Schema::hasColumn('cashiers', 'hire_date')) {
                        $table->date('hire_date')->nullable();
                    }
                    if (!Schema::hasColumn('cashiers', 'status')) {
                        $table->string('status', 50)->default('active');
                    }
                    if (!Schema::hasColumn('cashiers', 'register_number')) {
                        $table->string('register_number')->nullable();
                    }
                });
            } catch (\Throwable $e) {}
        }

        // Add profile fields to managers table
        if (Schema::hasTable('managers')) {
            try {
                Schema::table('managers', function (Blueprint $table) {
                    if (!Schema::hasColumn('managers', 'employee_code')) {
                        $table->string('employee_code')->nullable()->unique();
                    }
                    if (!Schema::hasColumn('managers', 'department')) {
                        $table->string('department')->nullable();
                    }
                    if (!Schema::hasColumn('managers', 'bio')) {
                        $table->text('bio')->nullable();
                    }
                    if (!Schema::hasColumn('managers', 'profile_photo')) {
                        $table->string('profile_photo')->nullable();
                    }
                    if (!Schema::hasColumn('managers', 'hire_date')) {
                        $table->date('hire_date')->nullable();
                    }
                    if (!Schema::hasColumn('managers', 'status')) {
                        $table->string('status', 50)->default('active');
                    }
                    if (!Schema::hasColumn('managers', 'permissions')) {
                        $table->json('permissions')->nullable();
                    }
                });
            } catch (\Throwable $e) {}
        }

        // Add profile fields to chefs table
        if (Schema::hasTable('chefs')) {
            try {
                Schema::table('chefs', function (Blueprint $table) {
                    if (!Schema::hasColumn('chefs', 'employee_code')) {
                        $table->string('employee_code')->nullable()->unique();
                    }
                    if (!Schema::hasColumn('chefs', 'specialization')) {
                        $table->string('specialization')->nullable();
                    }
                    if (!Schema::hasColumn('chefs', 'shift')) {
                        $table->string('shift')->nullable();
                    }
                    if (!Schema::hasColumn('chefs', 'experience_years')) {
                        $table->integer('experience_years')->nullable();
                    }
                    if (!Schema::hasColumn('chefs', 'bio')) {
                        $table->text('bio')->nullable();
                    }
                    if (!Schema::hasColumn('chefs', 'profile_photo')) {
                        $table->string('profile_photo')->nullable();
                    }
                    if (!Schema::hasColumn('chefs', 'hire_date')) {
                        $table->date('hire_date')->nullable();
                    }
                    if (!Schema::hasColumn('chefs', 'status')) {
                        $table->string('status', 50)->default('active');
                    }
                    if (!Schema::hasColumn('chefs', 'rank')) {
                        $table->string('rank', 50)->default('junior');
                    }
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        // Safe no-op on rollback
    }
};
