<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HotelFactory extends Factory
{
    protected $model = Hotel::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->company() . ' Hotel',
            'slug' => $this->faker->unique()->slug(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'logo' => null,
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'status' => Hotel::STATUS_ACTIVE,
        ];
    }
}
