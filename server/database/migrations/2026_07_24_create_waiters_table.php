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
            $table->enum('status', ['active', 'inactive', 'on_break'])->default('active');
            $table->json('current_tables')->nullable();
            $table->enum('shift', ['morning', 'afternoon', 'evening', 'night'])->default('morning');
            $table->enum('experience_level', ['junior', 'senior', 'head'])->default('junior');
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
