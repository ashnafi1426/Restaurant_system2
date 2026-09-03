<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('menu_item_reviews')) {
            Schema::create('menu_item_reviews', function (Blueprint $table) {
                // Primary key
                $table->uuid('id')->primary();
                
                // Foreign key columns
                $table->uuid('guest_id');
                $table->uuid('order_id');
                $table->uuid('menu_item_id');
                
                // Review content
                $table->unsignedTinyInteger('rating');
                $table->text('review_text')->nullable();
                
                // Status and moderation fields
                $table->string('status', 50)->default('pending');
                $table->uuid('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->uuid('rejected_by')->nullable();
                $table->timestamp('rejected_at')->nullable();
                
                // Helpfulness tracking
                $table->unsignedInteger('helpful_count')->default(0);
                $table->unsignedInteger('not_helpful_count')->default(0);
                
                // Timestamps
                $table->timestamps();
                $table->softDeletes();
                
                // Foreign key constraints
                $table->foreign('guest_id')
                    ->references('id')
                    ->on('guests')
                    ->onDelete('restrict');
                
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->onDelete('restrict');
                
                $table->foreign('menu_item_id')
                    ->references('id')
                    ->on('menu_items')
                    ->onDelete('cascade');
                
                $table->foreign('approved_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
                
                $table->foreign('rejected_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
                
                // Unique constraint - one review per guest per menu item per order
                $table->unique(['guest_id', 'order_id', 'menu_item_id'], 'unique_review_per_order');
                
                // Performance indexes
                $table->index(['menu_item_id', 'status'], 'idx_menu_item_status');
                $table->index(['status', 'created_at'], 'idx_status_created');
                $table->index(['guest_id', 'status'], 'idx_guest_pending');
            });
            
            try {
                DB::statement('ALTER TABLE menu_item_reviews ADD CONSTRAINT chk_rating_range CHECK (rating >= 1 AND rating <= 5)');
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item_reviews');
    }
};
