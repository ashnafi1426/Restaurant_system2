<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates restaurant_tables table for walk-in customer QR ordering.
     * Follows same QR pattern as rooms table.
     */
    public function up(): void
    {
        if (!Schema::hasTable('restaurant_tables')) {
            Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Table identification
            $table->string('table_number')->unique()->comment('Unique table identifier (e.g., "1", "5", "T10")');
            $table->string('table_name')->nullable()->comment('Optional descriptive name (e.g., "Window Table", "VIP Corner")');
            
            // Table properties
            $table->integer('capacity')->default(4)->comment('Number of seats/guests the table can accommodate');
            $table->string('location')->nullable()->comment('Physical location (e.g., "Main Hall", "Terrace", "VIP Section")');
            
            $table->string('status', 50)
                  ->default('available')
                  ->comment('Current table status');
            $table->boolean('is_active')->default(true)->comment('Whether table is active and can be used');
            
            // QR Code fields (matches rooms table pattern)
            $table->string('qr_token', 8)->unique()->nullable()->comment('Unique 8-character QR token');
            $table->string('qr_image_path')->nullable()->comment('Path to generated QR code image');
            $table->timestamp('qr_generated_at')->nullable()->comment('When QR code was last generated');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('table_number');
            $table->index('status');
            $table->index('qr_token');
            $table->index('is_active');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
