<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
                $table->uuid('granted_by')->nullable();
                $table->foreign('granted_by')->references('id')->on('users')->onDelete('set null');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'permission_id']);
                $table->index(['user_id', 'permission_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
