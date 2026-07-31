<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensure notification tables have proper schema for delivery task notifications
 * 
 * Purpose: Verify and update waiter_notifications and manager_notifications tables
 * to include delivery_task_id foreign key references.
 * 
 * Schema Requirements:
 * - waiter_notifications: waiter_id, delivery_task_id, type, title, message, read, timestamps
 * - manager_notifications: manager_id, delivery_task_id, type, title, message, read, timestamps
 * 
 * Both tables need indices on waiter_id/manager_id and read columns for query optimization.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update waiter_notifications table
        if (Schema::hasTable('waiter_notifications')) {
            Schema::table('waiter_notifications', function (Blueprint $table) {
                // Add waiter_id if it doesn't exist (migrate from user_id approach)
                if (!Schema::hasColumn('waiter_notifications', 'waiter_id')) {
                    $table->unsignedBigInteger('waiter_id')->nullable()->after('id');
                }
                
                // Add delivery_task_id if doesn't exist
                if (!Schema::hasColumn('waiter_notifications', 'delivery_task_id')) {
                    $table->uuid('delivery_task_id')->nullable()->after('waiter_id');
                }
                
                // Add 'read' column alias for is_read if doesn't exist
                if (!Schema::hasColumn('waiter_notifications', 'read')) {
                    $table->boolean('read')->default(false)->after('message');
                }
                
                // Add/ensure indices exist
                if (!Schema::hasIndex('waiter_notifications', 'waiter_notifications_waiter_id_index')) {
                    $table->index('waiter_id');
                }
                
                if (!Schema::hasIndex('waiter_notifications', 'waiter_notifications_read_index')) {
                    $table->index('read');
                }
                
                // Add foreign key for delivery_task_id if doesn't exist
                // First check if constraint exists before adding
                try {
                    if (!Schema::hasColumn('waiter_notifications', 'delivery_task_id')) {
                        return; // Column doesn't exist, skip FK
                    }
                    
                    // Try to add foreign key
                    $table->foreign('delivery_task_id')
                        ->references('id')
                        ->on('delivery_tasks')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist or column type mismatch
                    \Log::warning('Could not add delivery_task_id foreign key to waiter_notifications', [
                        'error' => $e->getMessage()
                    ]);
                }
            });
        }
        
        // Update manager_notifications table
        if (Schema::hasTable('manager_notifications')) {
            Schema::table('manager_notifications', function (Blueprint $table) {
                // Add delivery_task_id if doesn't exist
                if (!Schema::hasColumn('manager_notifications', 'delivery_task_id')) {
                    $table->uuid('delivery_task_id')->nullable()->after('manager_id');
                }
                
                // Add 'read' column alias for is_read if doesn't exist
                if (!Schema::hasColumn('manager_notifications', 'read')) {
                    $table->boolean('read')->default(false)->after('message');
                }
                
                // Add/ensure indices exist
                if (!Schema::hasIndex('manager_notifications', 'manager_notifications_manager_id_index')) {
                    $table->index('manager_id');
                }
                
                if (!Schema::hasIndex('manager_notifications', 'manager_notifications_read_index')) {
                    $table->index('read');
                }
                
                // Add foreign key for delivery_task_id if doesn't exist
                try {
                    if (!Schema::hasColumn('manager_notifications', 'delivery_task_id')) {
                        return; // Column doesn't exist, skip FK
                    }
                    
                    // Try to add foreign key
                    $table->foreign('delivery_task_id')
                        ->references('id')
                        ->on('delivery_tasks')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist or column type mismatch
                    \Log::warning('Could not add delivery_task_id foreign key to manager_notifications', [
                        'error' => $e->getMessage()
                    ]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback is complex due to foreign key dependencies
        // We'll just log and not remove columns (safer for production)
        \Log::info('Notification schema migration reversed - columns retained for safety');
    }
};
