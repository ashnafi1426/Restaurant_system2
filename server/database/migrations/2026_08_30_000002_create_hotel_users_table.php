<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hotel_id');
            $table->uuid('user_id');
            $table->string('role', 50)->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['hotel_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['hotel_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_users');
    }
};
