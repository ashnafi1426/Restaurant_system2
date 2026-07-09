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

            $table->enum('type', [
                'email',
                'sms',
                'push'
            ]);

            $table->string('subject')
                ->nullable();

            $table->text('content');

            $table->dateTime('sent_at')
                ->useCurrent();

            $table->enum('status', [
                'pending',
                'sent',
                'failed'
            ])->default('pending');

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
