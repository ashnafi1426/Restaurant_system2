<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $roomTypes = [
            [
                'name' => 'Standard',
                'description' => 'Basic room with essential amenities',
                'capacity' => 1,
                'base_price_per_night' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Deluxe',
                'description' => 'Comfortable room with premium amenities',
                'capacity' => 2,
                'base_price_per_night' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Suite',
                'description' => 'Luxury suite with living area and premium services',
                'capacity' => 4,
                'base_price_per_night' => 200,
                'is_active' => true,
            ],
            [
                'name' => 'Executive Suite',
                'description' => 'Top-tier suite with exclusive amenities',
                'capacity' => 2,
                'base_price_per_night' => 300,
                'is_active' => true,
            ],
        ];

        foreach ($roomTypes as $type) {
            RoomType::create([
                'id' => Str::uuid(),
                ...$type,
            ]);
        }
    }
}
