<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add profile fields to administrators table
        Schema::table('administrators', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->string('department')->nullable()->after('employee_code');
            $table->text('bio')->nullable()->after('department');
            $table->string('profile_photo')->nullable()->after('bio');
            $table->date('hire_date')->nullable()->after('profile_photo');
            $table->string('status', 50)->default('active')->after('hire_date');
        });

        // Add profile fields to receptionists table
        Schema::table('receptionists', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->string('shift')->nullable()->after('employee_code');
            $table->text('bio')->nullable()->after('shift');
            $table->string('profile_photo')->nullable()->after('bio');
            $table->date('hire_date')->nullable()->after('profile_photo');
            $table->string('status', 50)->default('active')->after('hire_date');
            $table->string('desk_number')->nullable()->after('status');
        });

        // Add profile fields to cashiers table
        Schema::table('cashiers', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->string('shift')->nullable()->after('employee_code');
            $table->text('bio')->nullable()->after('shift');
            $table->string('profile_photo')->nullable()->after('bio');
            $table->date('hire_date')->nullable()->after('profile_photo');
            $table->string('status', 50)->default('active')->after('hire_date');
            $table->string('register_number')->nullable()->after('status');
        });

        // Add profile fields to managers table
        Schema::table('managers', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->string('department')->nullable()->after('employee_code');
            $table->text('bio')->nullable()->after('department');
            $table->string('profile_photo')->nullable()->after('bio');
            $table->date('hire_date')->nullable()->after('profile_photo');
            $table->string('status', 50)->default('active')->after('hire_date');
            $table->json('permissions')->nullable()->after('status');
        });

        // Add profile fields to chefs table
        Schema::table('chefs', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->string('specialization')->nullable()->after('employee_code');
            $table->string('shift')->nullable()->after('specialization');
            $table->integer('experience_years')->nullable()->after('shift');
            $table->text('bio')->nullable()->after('experience_years');
            $table->string('profile_photo')->nullable()->after('bio');
            $table->date('hire_date')->nullable()->after('profile_photo');
            $table->string('status', 50)->default('active')->after('hire_date');
            $table->string('rank', 50)->default('junior')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('administrators', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'department', 'bio', 'profile_photo', 'hire_date', 'status']);
        });

        Schema::table('receptionists', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'shift', 'bio', 'profile_photo', 'hire_date', 'status', 'desk_number']);
        });

        Schema::table('cashiers', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'shift', 'bio', 'profile_photo', 'hire_date', 'status', 'register_number']);
        });

        Schema::table('managers', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'department', 'bio', 'profile_photo', 'hire_date', 'status', 'permissions']);
        });

        Schema::table('chefs', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'specialization', 'shift', 'experience_years', 'bio', 'profile_photo', 'hire_date', 'status', 'rank']);
        });
    }
};
