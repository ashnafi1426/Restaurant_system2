<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleUserSeeder::class,               // ← CREATE USERS (admin, manager, waiter, etc.)
            // RoomTypeSeeder::class,
            // RoomSeeder::class,
            // ReservationSeeder::class,
            // ClearMockMenuItemsSeeder::class,    // ← Remove all mock menu items
            // FixCashierPasswordSeeder::class,    // ← Fix cashier password and user info
            // ManagerSeeder::class,                // ← Create manager test data
            HotelShiftSeeder::class,             // ← Create shifts (prerequisite for floor assignments)
            WaiterManagementSeeder::class,       // ← Create waiter + floor + shift data
            // // WaiterSeeder::class,                 // ← Create additional waiter test data (commented - using WaiterManagementSeeder)
            DeliveryTaskSeeder::class,           // ← Create delivery tasks for testing
        ]);
    }
}