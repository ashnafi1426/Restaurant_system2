<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservation_id')->unique();
            $table->uuid('guest_id');
            $table->uuid('room_id');
            $table->dateTime('checked_in_at');
            $table->dateTime('expected_check_out_at');
            $table->dateTime('checked_out_at')->nullable();
            $table->timestamps();
            $table->foreign('reservation_id')->references('id')->on('reservations');
            $table->foreign('guest_id')->references('id')->on('guests');
            $table->foreign('room_id')->references('id')->on('rooms');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
