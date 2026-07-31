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
        if (!Schema::hasTable('waiter_notifications')) {
            Schema::create('waiter_notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('type'); // 'order_assigned', 'order_ready', 'delivery_completed', etc.
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable(); // Additional data like order_id, etc.
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('user_id');
                $table->index('is_read');
                $table->index('type');
                $table->index('created_at');

                // Foreign key
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_notifications');
    }
};
