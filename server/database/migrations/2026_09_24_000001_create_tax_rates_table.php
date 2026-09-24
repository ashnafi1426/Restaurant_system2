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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hotel_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('percentage'); // percentage, fixed, vat, service
            $table->decimal('rate', 5, 2)->default(0.00);
            $table->string('applies_to')->default('food_beverage'); // food_beverage, room, general, service
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')
                ->references('id')
                ->on('hotels')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
