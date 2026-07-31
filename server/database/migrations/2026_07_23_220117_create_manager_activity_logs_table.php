<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_activity_logs', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('manager_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('module');

            $table->string('action');

            $table->string('reference_type');

            $table->uuid('reference_id')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->ipAddress('ip_address')
                ->nullable();

            $table->string('device')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_activity_logs');
    }
};