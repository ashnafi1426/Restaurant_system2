<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
            });

            // Seed default platform settings
            $defaults = [
                ['key' => 'platform_name', 'value' => 'HMS Multi-Hotel Platform', 'description' => 'Platform display name'],
                ['key' => 'default_currency', 'value' => 'ETB', 'description' => 'Default system currency'],
                ['key' => 'default_timezone', 'value' => 'Africa/Addis_Ababa', 'description' => 'Default system timezone'],
                ['key' => 'maintenance_mode', 'value' => 'false', 'description' => 'System-wide maintenance mode'],
                ['key' => 'support_email', 'value' => 'support@hmsplatform.com', 'description' => 'Global support contact email'],
                ['key' => 'allow_hotel_registration', 'value' => 'true', 'description' => 'Allow self-onboarding requests'],
            ];

            foreach ($defaults as $setting) {
                \Illuminate\Support\Facades\DB::table('platform_settings')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
