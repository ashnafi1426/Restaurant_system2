<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\HotelFloor;

class HotelFloorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $floors = [
            [
                'floor_number' => 1,
                'name' => 'Ground Floor',
                'description' => 'Ground floor with restaurant, lobby, and reception',
                'is_active' => true,
                'total_rooms' => 0,
            ],
            [
                'floor_number' => 2,
                'name' => 'First Floor',
                'description' => 'First floor with standard rooms',
                'is_active' => true,
                'total_rooms' => 10,
            ],
            [
                'floor_number' => 3,
                'name' => 'Second Floor',
                'description' => 'Second floor with deluxe rooms',
                'is_active' => true,
                'total_rooms' => 10,
            ],
            [
                'floor_number' => 4,
                'name' => 'Third Floor',
                'description' => 'Third floor with suite rooms',
                'is_active' => true,
                'total_rooms' => 8,
            ],
            [
                'floor_number' => 5,
                'name' => 'Fourth Floor',
                'description' => 'Fourth floor with executive suites',
                'is_active' => true,
                'total_rooms' => 6,
            ],
        ];

        foreach ($floors as $floor) {
            HotelFloor::create([
                'id' => Str::uuid(),
                ...$floor,
            ]);
        }

        $this->command->info('Hotel floors seeded successfully!');
    }
}
