<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleUserSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            ReservationSeeder::class,
            ClearMockMenuItemsSeeder::class,  // ← Remove all mock menu items
            FixCashierPasswordSeeder::class,  // ← Fix cashier password and user info
        ]);
    }
}