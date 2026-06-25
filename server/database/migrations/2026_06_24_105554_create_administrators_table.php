<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrators', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreign('id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('administrators');
    }
};
