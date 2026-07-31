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
        if (!Schema::hasTable('performance_metrics')) {
            Schema::create('performance_metrics', function (Blueprint $table) {
                $table->id();
                $table->uuid('staff_id')->index();
                $table->string('department');
                $table->date('metric_date');
                
                // Common metrics
                $table->integer('tasks_assigned')->default(0);
                $table->integer('tasks_completed')->default(0);
                $table->integer('tasks_pending')->default(0);
                $table->decimal('completion_rate', 5, 2)->default(0);
                
                // Time metrics
                $table->integer('avg_task_duration_minutes')->nullable();
                $table->integer('late_tasks')->default(0);
                $table->decimal('on_time_rate', 5, 2)->default(100);
                
                // Quality metrics
                $table->decimal('quality_score', 5, 2)->default(0);
                $table->integer('customer_complaints')->default(0);
                $table->decimal('satisfaction_rating', 3, 2)->nullable();
                
                // Department specific
                $table->integer('orders_prepared')->nullable();
                $table->integer('orders_rejected')->nullable();
                $table->integer('deliveries_completed')->nullable();
                $table->integer('deliveries_failed')->nullable();
                $table->integer('rooms_cleaned')->nullable();
                $table->integer('inspection_passes')->nullable();
                
                $table->json('notes')->nullable();
                $table->timestamps();

                $table->foreign('staff_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->index(['staff_id', 'metric_date']);
                $table->index(['department', 'metric_date']);
                $table->unique(['staff_id', 'department', 'metric_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
