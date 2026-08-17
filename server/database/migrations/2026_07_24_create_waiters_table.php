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
        Schema::create('waiters', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('section')->nullable(); 
            $table->string('status', 50)->default('active');
            $table->json('current_tables')->nullable();
            $table->string('shift', 50)->default('morning');
            $table->string('experience_level', 50)->default('junior');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiters');
    }
};
