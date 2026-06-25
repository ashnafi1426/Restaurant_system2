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
        Schema::create('hotel_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hotel_name');
            $table->text('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->decimal('tax_rate', 5, 2)
                ->default(0);
            $table->decimal('service_charge_percent', 5, 2)
                ->default(0);
            $table->time('check_in_time')
                ->default('14:00:00');
            $table->time('check_out_time')
                ->default('11:00:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_settings');
    }
};
