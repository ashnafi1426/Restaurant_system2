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
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hotel_id')->index();
            $table->string('name');
            $table->string('type'); // flexible, moderate, strict, non_refundable
            $table->text('description')->nullable();
            $table->integer('cancellation_deadline_days')->default(7); // Days before check-in
            $table->decimal('refund_percentage', 5, 2)->default(100); // Percentage of refund
            $table->integer('minimum_stay_nights')->default(1);
            $table->string('applies_to')->default('all'); // all, room_type_id, etc.
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('hotel_id')
                ->references('id')
                ->on('hotels')
                ->onDelete('cascade');

            // Indexes
            $table->index(['hotel_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
    }
};
