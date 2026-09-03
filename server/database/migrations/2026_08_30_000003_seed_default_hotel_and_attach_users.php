<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Hotel;
use App\Models\User;
use App\Models\HotelUser;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create or retrieve Hotel #1
        $hotel = Hotel::where('slug', 'executive-horizon')->first();

        if (!$hotel) {
            $settings = Schema::hasTable('hotel_settings') ? DB::table('hotel_settings')->first() : null;

            $hotel = Hotel::create([
                'id' => (string) Str::uuid(),
                'name' => $settings->hotel_name ?? env('HOTEL_NAME', 'Executive Horizon Hotel'),
                'slug' => 'executive-horizon',
                'email' => $settings->email ?? env('HOTEL_EMAIL', 'support@executivehorizon.com'),
                'phone' => $settings->phone ?? env('HOTEL_PHONE', '+251-800-000-0000'),
                'address' => $settings->address ?? 'Addis Ababa, Ethiopia',
                'city' => 'Addis Ababa',
                'country' => 'Ethiopia',
                'timezone' => 'Africa/Addis_Ababa',
                'currency' => 'ETB',
                'status' => Hotel::STATUS_ACTIVE,
            ]);
        }

        // 2. Attach all existing users to Hotel #1 with their current role
        $users = User::all();
        foreach ($users as $user) {
            $userRole = strtolower($user->role ?: 'admin');

            HotelUser::firstOrCreate(
                [
                    'hotel_id' => $hotel->id,
                    'user_id' => $user->id,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'role' => $userRole,
                    'is_active' => (bool) $user->is_active,
                ]
            );
        }
    }

    public function down(): void
    {
        $hotel = Hotel::where('slug', 'executive-horizon')->first();
        if ($hotel) {
            HotelUser::where('hotel_id', $hotel->id)->delete();
            $hotel->delete();
        }
    }
};
