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
        // This table was already created by 2026_07_23_220117_create_manager_notifications_table.php
        // Just ensure UUID foreign key is set
        if (Schema::hasTable('manager_notifications')) {
            Schema::table('manager_notifications', function (Blueprint $table) {
                try {
                    $table->dropForeign(['manager_id']);
                } catch (\Exception $e) {
                    // Already dropped
                }
                
                // Ensure correct type
                if (Schema::hasColumn('manager_notifications', 'manager_id')) {
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
        Schema::dropIfExists('manager_notifications');
    }
};
