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
        Schema::table('rooms', function (Blueprint $table) {
            // Check if qr_token column exists, if not add it first
            if (!Schema::hasColumn('rooms', 'qr_token')) {
                $table->string('qr_token')->nullable()->unique();
            }
            
            // Store path to the generated QR code image
            if (!Schema::hasColumn('rooms', 'qr_image_path')) {
                $table->string('qr_image_path')->nullable()->after('qr_token');
            }
            
            // Track when QR code was last generated (for regeneration if needed)
            if (!Schema::hasColumn('rooms', 'qr_generated_at')) {
                $table->timestamp('qr_generated_at')->nullable()->after('qr_image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['qr_image_path', 'qr_generated_at']);
        });
    }
};
