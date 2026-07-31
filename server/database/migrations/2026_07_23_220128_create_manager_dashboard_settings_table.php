<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_dashboard_settings', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('manager_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('show_revenue')
                ->default(true);

            $table->boolean('show_rooms')
                ->default(true);

            $table->boolean('show_orders')
                ->default(true);

            $table->boolean('show_housekeeping')
                ->default(true);

            $table->boolean('show_laundry')
                ->default(true);

            $table->boolean('show_notifications')
                ->default(true);

            $table->string('theme')
                ->default('light');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_dashboard_settings');
    }
};