<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FloorFactory extends Factory
{
    protected $model = Floor::class;

    public function definition(): array
    {
        $floorNumber = $this->faker->numberBetween(1, 10);
        
        return [
            'id' => (string) Str::uuid(),
            'hotel_id' => Hotel::factory(),
            'name' => 'Floor ' . $floorNumber,
            'floor_number' => $floorNumber,
            'description' => $this->faker->optional()->sentence(),
            'total_rooms' => $this->faker->numberBetween(5, 30),
            'is_active' => true,
        ];
    }
}
