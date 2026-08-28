<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => Order::generateOrderNumber(),
            'guest_id' => Guest::factory(),
            'order_type' => Order::TYPE_ROOM_SERVICE,
            'order_time' => now(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => fake()->randomFloat(2, 10, 200),
            'tax' => fake()->randomFloat(2, 1, 20),
            'discount' => 0,
            'total' => fake()->randomFloat(2, 10, 220),
        ];
    }

    /**
     * Indicate that the order is served (completed).
     */
    public function served(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_SERVED,
            'served_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the order is preparing.
     */
    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PREPARING,
        ]);
    }
}
