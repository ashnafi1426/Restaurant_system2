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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('type', ['booking', 'check_in', 'check_out', 'cancellation', 'system'])->default('booking');
            $table->string('title');
            $table->text('message');
            
            // Optional relations
            $table->uuid('reservation_id')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('room_number')->nullable();
            $table->string('room_type')->nullable();
            $table->dateTime('check_in_date')->nullable();
            $table->dateTime('check_out_date')->nullable();
            
            // Status
            $table->boolean('read')->default(false);
            $table->index('read');
            $table->index('type');
            $table->index('user_id');
            $table->index('created_at');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
