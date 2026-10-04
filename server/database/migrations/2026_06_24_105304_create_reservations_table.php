<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_reference', 20)->unique();
            $table->uuid('guest_id');
            $table->uuid('room_id');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('number_of_guests');
            $isPgsql = DB::connection()->getDriverName() === 'pgsql';
            $table->integer('total_nights')->storedAs(
                $isPgsql ? '(check_out_date - check_in_date)' : 'DATEDIFF(check_out_date, check_in_date)'
            );
            $table->string('status', 50)->default('pending');
            $table->text('special_requests')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->index('guest_id');
            $table->index('room_id');
            $table->index(['check_in_date', 'check_out_date']);
            $table->index('status');
            $table->foreign('guest_id')
                ->references('id')
                ->on('guests');
            $table->foreign('room_id')
                ->references('id')
                ->on('rooms');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
