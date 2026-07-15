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
            // Add QR token column - unique random token for each room
            $table->string('qr_token')->unique()->nullable()->after('room_number');
        });

        // Generate unique QR tokens for existing rooms
        $rooms = \DB::table('rooms')->get();
        foreach ($rooms as $room) {
            if (is_null($room->qr_token)) {
                \DB::table('rooms')
                    ->where('id', $room->id)
                    ->update(['qr_token' => $this->generateUniqueToken()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }

    /**
     * Generate a unique token for QR code
     * Format: 8 characters, alphanumeric, easy to type if needed
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (\DB::table('rooms')->where('qr_token', $token)->exists());

        return $token;
    }
};
