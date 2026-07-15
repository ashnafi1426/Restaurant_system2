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
            // Store path to the generated QR code image
            $table->string('qr_image_path')->nullable()->after('qr_token');
            // Track when QR code was last generated (for regeneration if needed)
            $table->timestamp('qr_generated_at')->nullable()->after('qr_image_path');
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
