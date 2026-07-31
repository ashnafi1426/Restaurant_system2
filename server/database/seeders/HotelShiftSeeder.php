<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\HotelShift;

class HotelShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Morning',
                'start_time' => '06:00',
                'end_time' => '14:00',
                'status' => 'active',
                'description' => 'Early morning shift',
            ],
            [
                'name' => 'Afternoon',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'status' => 'active',
                'description' => 'Afternoon shift',
            ],
            [
                'name' => 'Evening',
                'start_time' => '17:00',
                'end_time' => '23:00',
                'status' => 'active',
                'description' => 'Evening shift',
            ],
            [
                'name' => 'Night',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'status' => 'active',
                'description' => 'Night shift',
            ],
        ];

        foreach ($shifts as $shift) {
            HotelShift::create([
                'id' => Str::uuid(),
                ...$shift,
            ]);
        }

        $this->command->info('Hotel shifts seeded successfully!');
    }
}
