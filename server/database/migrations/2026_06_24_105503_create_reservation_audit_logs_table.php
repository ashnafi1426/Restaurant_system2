<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservation_id');
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->uuid('actor_id')->nullable();
            $table->timestamp('action_at')
                ->useCurrent();
            $table->foreign('reservation_id')
                ->references('id')
                ->on('reservations')
                ->cascadeOnDelete();
            $table->foreign('actor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_audit_logs');
    }
};
