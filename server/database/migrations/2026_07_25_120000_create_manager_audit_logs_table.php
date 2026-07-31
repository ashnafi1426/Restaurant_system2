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
        Schema::create('manager_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('manager_id')->index();
            $table->string('action'); // 'view', 'assign', 'escalate', 'resolve', etc.
            $table->string('resource_type'); // 'waiter', 'complaint', 'order', etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable(); // For tracking changes
            $table->json('new_values')->nullable(); // For tracking changes
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index(['manager_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_audit_logs');
    }
};
