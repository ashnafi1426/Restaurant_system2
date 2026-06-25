<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('room_number')
                ->unique();
            $table->uuid('room_type_id');
            $table->integer('floor')
                ->nullable();
            $table->text('description')
                ->nullable();
            $table->enum('status', [
                'available',
                'reserved',
                'occupied',
                'cleaning',
                'maintenance'
            ])->default('available');
            $table->boolean('is_active')
                ->default(true);
            $table->timestamps();
            $table->index('status');
            $table->foreign('room_type_id')
                ->references('id')
                ->on('room_types');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
