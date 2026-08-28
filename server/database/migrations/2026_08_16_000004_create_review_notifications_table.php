<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('review_id')
                ->constrained('menu_item_reviews')
                ->cascadeOnDelete();

            $table->string('notification_type');
            // new_review
            // review_approved
            // review_rejected

            $table->text('message');

            $table->boolean('is_read')
                ->default(false);

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('read_at')
                ->nullable();

            // Index for efficient queries
            $table->index(['user_id', 'is_read', 'created_at'], 'idx_user_unread');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_notifications');
    }
};
