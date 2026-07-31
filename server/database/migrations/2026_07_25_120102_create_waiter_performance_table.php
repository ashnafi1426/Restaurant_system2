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
        if (!Schema::hasTable('waiter_performance')) {
            Schema::create('waiter_performance', function (Blueprint $table) {
                $table->id();
                $table->uuid('waiter_id')->index();
                $table->date('metric_date')->index();
                
                // Assignment Metrics
                $table->integer('deliveries_assigned')->default(0);
                $table->integer('deliveries_accepted')->default(0);
                $table->integer('deliveries_rejected')->default(0);
                $table->decimal('acceptance_rate', 5, 2)->default(100); // 0-100%
                
                // Completion Metrics
                $table->integer('deliveries_completed')->default(0);
                $table->integer('deliveries_failed')->default(0);
                $table->decimal('completion_rate', 5, 2)->default(0); // 0-100%
                
                // Time Metrics
                $table->integer('avg_delivery_time_minutes')->nullable();
                $table->integer('on_time_deliveries')->default(0);
                $table->decimal('on_time_rate', 5, 2)->default(100); // 0-100%
                
                // Quality Metrics
                $table->decimal('guest_rating_avg', 3, 2)->nullable(); // 1-5
                $table->decimal('rating', 3, 2)->nullable(); // Overall rating 1-5
                $table->integer('total_ratings')->default(0);
                $table->text('notes')->nullable();
                
                $table->timestamps();

                $table->foreign('waiter_id')->references('id')->on('users')->onDelete('cascade');
                
                // Unique per waiter per day
                $table->unique(['waiter_id', 'metric_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_performance');
    }
};
