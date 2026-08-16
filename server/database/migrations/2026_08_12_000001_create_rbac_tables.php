<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'display_name')) {
                    $table->string('display_name')->nullable();
                }
                if (!Schema::hasColumn('roles', 'slug')) {
                    $table->string('slug')->nullable()->unique();
                }
                if (!Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('roles', 'is_system')) {
                    $table->boolean('is_system')->default(false);
                }
                if (!Schema::hasColumn('roles', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        // 2. Permissions table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('module');
                $table->string('action');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('permissions', 'module')) {
                    $table->string('module')->default('general');
                }
                if (!Schema::hasColumn('permissions', 'action')) {
                    $table->string('action')->default('view');
                }
                if (!Schema::hasColumn('permissions', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('permissions', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        // 3. Role-Permissions pivot table
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['role_id', 'permission_id']);
            });
        }

        // 4. User-Roles pivot table
        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
            });
        }

        // 5. Temporary Role Assignments table
        if (!Schema::hasTable('temporary_role_assignments')) {
            Schema::create('temporary_role_assignments', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->timestamp('starts_at');
                $table->timestamp('expires_at');
                $table->uuid('assigned_by')->nullable();
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
                $table->string('reason')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 6. RBAC Audit Logs table
        if (!Schema::hasTable('rbac_audit_logs')) {
            Schema::create('rbac_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->string('action');
                $table->string('target_type')->nullable();
                $table->string('target_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rbac_audit_logs');
        Schema::dropIfExists('temporary_role_assignments');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
