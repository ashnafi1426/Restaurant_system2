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
        Schema::create('review_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('review_id');
            $table->uuid('responder_id')->nullable();
            $table->string('response_text', 500);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('review_id')->references('id')->on('menu_item_reviews')->onDelete('cascade');
            $table->foreign('responder_id')->references('id')->on('users')->onDelete('set null');
            
            // Unique constraint - one response per review
            $table->unique('review_id', 'unique_response_per_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_responses');
    }
};
