<?php

namespace Database\Factories;

use App\Models\RoomType;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'hotel_id' => Hotel::factory(),
            'name' => $this->faker->randomElement(['Deluxe', 'Standard', 'Suite', 'Economy', 'Presidential', 'Executive']),
            'description' => $this->faker->sentence(),
            'base_price_per_night' => $this->faker->randomFloat(2, 50, 500),
            'capacity' => $this->faker->numberBetween(1, 6),
            'amenities' => [
                $this->faker->randomElement(['WiFi', 'TV', 'Air Conditioning', 'Mini Bar', 'Room Service']),
                $this->faker->randomElement(['Balcony', 'Sea View', 'City View', 'Bathtub', 'Jacuzzi']),
            ],
            'is_active' => true,
        ];
    }
}
