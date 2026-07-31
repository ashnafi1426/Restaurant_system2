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
        // Fix waiter_assignments table
        if (Schema::hasTable('waiter_assignments')) {
            Schema::table('waiter_assignments', function (Blueprint $table) {
                // Drop existing foreign keys first
                try {
                    $table->dropForeign(['waiter_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }
                try {
                    $table->dropForeign(['assigned_by']);
                } catch (\Exception $e) {
                    // Key might not exist
                }

                // Modify columns to UUID if they're currently big integer
                if (Schema::hasColumn('waiter_assignments', 'waiter_id')) {
                    $table->uuid('waiter_id')->change();
                }
                if (Schema::hasColumn('waiter_assignments', 'assigned_by')) {
                    $table->uuid('assigned_by')->nullable()->change();
                }

                // Re-add foreign keys
                $table->foreign('waiter_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Fix waiter_performance table
        if (Schema::hasTable('waiter_performance')) {
            Schema::table('waiter_performance', function (Blueprint $table) {
                try {
                    $table->dropForeign(['waiter_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }

                if (Schema::hasColumn('waiter_performance', 'waiter_id')) {
                    $table->uuid('waiter_id')->change();
                }

                $table->foreign('waiter_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Fix delivery_logs table
        if (Schema::hasTable('delivery_logs')) {
            Schema::table('delivery_logs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['waiter_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }

                if (Schema::hasColumn('delivery_logs', 'waiter_id')) {
                    $table->uuid('waiter_id')->change();
                }

                $table->foreign('waiter_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Fix manager_audit_logs table
        if (Schema::hasTable('manager_audit_logs')) {
            Schema::table('manager_audit_logs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['manager_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }

                if (Schema::hasColumn('manager_audit_logs', 'manager_id')) {
                    $table->uuid('manager_id')->change();
                }

                $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Fix manager_notifications table
        if (Schema::hasTable('manager_notifications')) {
            Schema::table('manager_notifications', function (Blueprint $table) {
                try {
                    $table->dropForeign(['manager_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }

                if (Schema::hasColumn('manager_notifications', 'manager_id')) {
                    $table->uuid('manager_id')->change();
                }

                $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Fix complaint_tickets table
        if (Schema::hasTable('complaint_tickets')) {
            Schema::table('complaint_tickets', function (Blueprint $table) {
                try {
                    $table->dropForeign(['guest_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }
                try {
                    $table->dropForeign(['manager_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }
                try {
                    $table->dropForeign(['assigned_to']);
                } catch (\Exception $e) {
                    // Key might not exist
                }

                if (Schema::hasColumn('complaint_tickets', 'guest_id')) {
                    $table->uuid('guest_id')->nullable()->change();
                }
                if (Schema::hasColumn('complaint_tickets', 'manager_id')) {
                    $table->uuid('manager_id')->nullable()->change();
                }
                if (Schema::hasColumn('complaint_tickets', 'assigned_to')) {
                    $table->uuid('assigned_to')->nullable()->change();
                }

                $table->foreign('guest_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Fix manager_reports table
        if (Schema::hasTable('manager_reports')) {
            Schema::table('manager_reports', function (Blueprint $table) {
                try {
                    $table->dropForeign(['manager_id']);
                } catch (\Exception $e) {
                    // Key might not exist
                }

                if (Schema::hasColumn('manager_reports', 'manager_id')) {
                    $table->uuid('manager_id')->change();
                }

                $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is a fix, so reverse would be risky
        // Leave empty to prevent data loss on rollback
    }
};
