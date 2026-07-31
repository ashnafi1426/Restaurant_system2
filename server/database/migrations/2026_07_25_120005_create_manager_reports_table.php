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
        if (!Schema::hasTable('manager_reports')) {
            Schema::create('manager_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('manager_id');
            $table->string('report_type'); // 'daily', 'weekly', 'monthly', 'yearly', 'department', 'revenue', 'performance'
            $table->string('report_name');
            $table->date('report_date');
            $table->date('period_start');
            $table->date('period_end');
            
            // Financial Metrics
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('daily_revenue', 12, 2)->default(0);
            $table->decimal('pending_payments', 12, 2)->default(0);
            
            // Operational Metrics
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('failed_orders')->default(0);
            $table->integer('total_guests')->default(0);
            $table->integer('checked_in_guests')->default(0);
            $table->integer('checked_out_guests')->default(0);
            
            // Room Metrics
            $table->integer('total_rooms')->default(0);
            $table->integer('occupied_rooms')->default(0);
            $table->decimal('occupancy_rate', 5, 2)->default(0);
            $table->integer('available_rooms')->default(0);
            $table->integer('maintenance_rooms')->default(0);
            
            // Service Metrics
            $table->integer('total_complaints')->default(0);
            $table->integer('resolved_complaints')->default(0);
            $table->integer('pending_complaints')->default(0);
            $table->decimal('complaint_resolution_rate', 5, 2)->default(0);
            
            // Staff Metrics
            $table->integer('staff_on_duty')->default(0);
            $table->decimal('avg_staff_performance', 5, 2)->default(0);
            $table->integer('kitchen_orders')->default(0);
            $table->integer('kitchen_completed')->default(0);
            $table->integer('kitchen_rejected')->default(0);
            $table->integer('avg_prep_time_minutes')->nullable();
            
            $table->json('summary')->nullable(); // Additional summary data
            $table->json('charts_data')->nullable(); // Chart data for frontend
            $table->string('status')->default('draft'); // 'draft', 'generated', 'sent'
            $table->datetime('generated_at')->nullable();
            $table->datetime('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index(['manager_id', 'report_type', 'report_date']);
            $table->index(['report_type', 'period_start', 'period_end']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_reports');
    }
};
