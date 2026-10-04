<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Floor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'hotel_id' => Hotel::factory(),
            'room_number' => $this->faker->unique()->numberBetween(101, 999),
            'room_type_id' => RoomType::factory(),
            'floor_id' => Floor::factory(),
            'floor' => $this->faker->numberBetween(1, 10),
            'description' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['available', 'occupied', 'reserved', 'cleaning', 'maintenance']),
            'is_active' => true,
            'qr_token' => strtoupper(Str::random(8)),
            'qr_image_path' => null,
            'qr_generated_at' => null,
        ];
    }
}
