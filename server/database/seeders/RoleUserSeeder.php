<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Hotel;
use App\Models\HotelUser;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Platform Super Admin (System-wide Administrator)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@hotel.com'],
            [
                'id' => (string) Str::uuid(),
                'first_name' => 'Platform',
                'last_name' => 'SuperAdmin',
                'phone' => '0911111111',
                'password_hash' => Hash::make('Admin123@'),
                'role' => 'admin',
                'is_active' => true,
                'is_platform_admin' => true,
            ]
        );

//         // Ensure is_platform_admin flag is set on super admin
//         if (!$superAdmin->is_platform_admin) {
//             $superAdmin->update(['is_platform_admin' => true]);
//         }

//         // 2. Ensure Hotel #1 ("Executive Horizon Hotel") exists
//         $hotel1 = Hotel::firstOrCreate(
//             ['slug' => 'executive-horizon-hotel'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'name' => 'Executive Horizon Hotel',
//                 'status' => Hotel::STATUS_ACTIVE,
//                 'email' => 'info@horizonhotel.com',
//                 'phone' => '+251 11 555 0100',
//                 'address' => 'Bole Sub-City, Kebele 03',
//                 'city' => 'Addis Ababa',
//                 'country' => 'Ethiopia',
//                 'currency' => 'ETB',
//                 'timezone' => 'Africa/Addis_Ababa',
//             ]
//         );

//         // 3. Ensure Hotel #2 ("Lalibela Palace Hotel") exists
//         $hotel2 = Hotel::firstOrCreate(
//             ['slug' => 'lalibela-palace-hotel'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'name' => 'Lalibela Palace Hotel',
//                 'status' => Hotel::STATUS_ACTIVE,
//                 'email' => 'contact@lalibelapalace.com',
//                 'phone' => '+251 58 335 0200',
//                 'address' => 'Church Road, Hill View',
//                 'city' => 'Lalibela',
//                 'country' => 'Ethiopia',
//                 'currency' => 'ETB',
//                 'timezone' => 'Africa/Addis_Ababa',
//             ]
//         );

//         // 4. Hotel #1 Dedicated Admin
//         $hotel1Admin = User::firstOrCreate(
//             ['email' => 'hotel1.admin@hotel.com'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'first_name' => 'Horizon',
//                 'last_name' => 'Admin',
//                 'phone' => '0911111101',
//                 'password_hash' => Hash::make('Hotel1Admin123@'),
//                 'role' => 'admin',
//                 'is_active' => true,
//                 'is_platform_admin' => false,
//             ]
//         );

//         // 5. Hotel #2 Dedicated Admin
//         $hotel2Admin = User::firstOrCreate(
//             ['email' => 'hotel2.admin@hotel.com'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'first_name' => 'Lalibela',
//                 'last_name' => 'Admin',
//                 'phone' => '0911111102',
//                 'password_hash' => Hash::make('Hotel2Admin123@'),
//                 'role' => 'admin',
//                 'is_active' => true,
//                 'is_platform_admin' => false,
//             ]
//         );

//         // 6. Hotel Staff Users
//         $receptionist = User::firstOrCreate(
//             ['email' => 'receptionist@hotel.com'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'first_name' => 'John',
//                 'last_name' => 'Reception',
//                 'phone' => '0922222222',
//                 'password_hash' => Hash::make('Reception123@'),
//                 'role' => 'receptionist',
//                 'is_active' => true,
//                 'is_platform_admin' => false,
//             ]
//         );

//         $cashier = User::firstOrCreate(
//             ['email' => 'cashier@hotel.com'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'first_name' => 'Sarah',
//                 'last_name' => 'Cashier',
//                 'phone' => '0933333333',
//                 'password_hash' => Hash::make('Cashier123@'),
//                 'role' => 'cashier',
//                 'is_active' => true,
//                 'is_platform_admin' => false,
//             ]
//         );

//         $manager = User::firstOrCreate(
//             ['email' => 'manager@hotel.com'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'first_name' => 'David',
//                 'last_name' => 'Manager',
//                 'phone' => '0944444444',
//                 'password_hash' => Hash::make('Manager123@'),
//                 'role' => 'manager',
//                 'is_active' => true,
//                 'is_platform_admin' => false,
//             ]
//         );

//         $chef = User::firstOrCreate(
//             ['email' => 'chef@hotel.com'],
//             [
//                 'id' => (string) Str::uuid(),
//                 'first_name' => 'Michael',
//                 'last_name' => 'Chef',
//                 'phone' => '0955555555',
//                 'password_hash' => Hash::make('Chef123@'),
//                 'role' => 'chef',
//                 'is_active' => true,
//                 'is_platform_admin' => false,
//             ]
//         );

//         // 7. Bind memberships in hotel_users
//         if (Schema::hasTable('hotel_users')) {
//             // Super Admin belongs to both hotels
//             HotelUser::firstOrCreate(
//                 ['hotel_id' => $hotel1->id, 'user_id' => $superAdmin->id],
//                 ['id' => (string) Str::uuid(), 'role' => 'admin', 'is_active' => true]
//             );
//             HotelUser::firstOrCreate(
//                 ['hotel_id' => $hotel2->id, 'user_id' => $superAdmin->id],
//                 ['id' => (string) Str::uuid(), 'role' => 'admin', 'is_active' => true]
//             );

//             // Hotel 1 Admin belongs ONLY to Hotel 1
//             HotelUser::firstOrCreate(
//                 ['hotel_id' => $hotel1->id, 'user_id' => $hotel1Admin->id],
//                 ['id' => (string) Str::uuid(), 'role' => 'admin', 'is_active' => true]
//             );

//             // Hotel 2 Admin belongs ONLY to Hotel 2
//             HotelUser::firstOrCreate(
//                 ['hotel_id' => $hotel2->id, 'user_id' => $hotel2Admin->id],
//                 ['id' => (string) Str::uuid(), 'role' => 'admin', 'is_active' => true]
//             );

//             // Staff members belong to Hotel 1
//             foreach ([$receptionist, $cashier, $manager, $chef] as $staff) {
//                 HotelUser::firstOrCreate(
//                     ['hotel_id' => $hotel1->id, 'user_id' => $staff->id],
//                     ['id' => (string) Str::uuid(), 'role' => $staff->role ?: 'staff', 'is_active' => true]
//                 );
//             }
//         }
    }
}
