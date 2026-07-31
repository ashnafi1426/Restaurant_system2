<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_reports', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('manager_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('report_name');

            $table->string('report_type');
            // daily
            // weekly
            // monthly
            // yearly

            $table->date('from_date');

            $table->date('to_date');

            $table->string('file_path')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_reports');
    }
};