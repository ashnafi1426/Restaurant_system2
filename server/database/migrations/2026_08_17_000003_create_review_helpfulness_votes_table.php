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
        if (!Schema::hasTable('review_helpfulness_votes')) {
            Schema::create('review_helpfulness_votes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('review_id');
                $table->uuid('guest_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('vote_type', 50);
                $table->timestamp('created_at')->useCurrent();
                
                // Foreign keys
                $table->foreign('review_id')->references('id')->on('menu_item_reviews')->onDelete('cascade');
                $table->foreign('guest_id')->references('id')->on('guests')->onDelete('set null');
                
                // Unique constraints
                $table->unique(['review_id', 'guest_id'], 'unique_guest_vote');
                $table->unique(['review_id', 'ip_address'], 'unique_ip_vote');
                
                // Index
                $table->index(['review_id', 'vote_type'], 'idx_review_votes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_helpfulness_votes');
    }
};
