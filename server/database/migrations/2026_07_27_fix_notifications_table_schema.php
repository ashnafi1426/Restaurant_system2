<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a backup of the old notifications table
        DB::statement('CREATE TABLE IF NOT EXISTS notifications_backup_old AS SELECT * FROM notifications');
        
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Drop foreign keys and table
        DB::statement('DROP TABLE IF EXISTS notifications');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Create the new notifications table with the correct schema
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->enum('type', [
                'reservation',
                'order',
                'delivery',
                'general'
            ])->default('general');
            $table->string('title');
            $table->text('message');
            $table->uuid('reservation_id')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('room_number')->nullable();
            $table->string('room_type')->nullable();
            $table->dateTime('check_in_date')->nullable();
            $table->dateTime('check_out_date')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamps();

            $table->foreign('user_id', 'fk_notifications_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'read']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notifications_backup_old');
    }
};
