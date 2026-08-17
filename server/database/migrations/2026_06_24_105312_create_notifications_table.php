<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->uuid('user_id');

            $table->string('type', 100)->default('general');

            $table->string('subject')
                ->nullable();

            $table->text('content');

            $table->dateTime('sent_at')
                ->useCurrent();

            $table->string('status', 50)->default('pending');

            $table->text('error_message')
                ->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
