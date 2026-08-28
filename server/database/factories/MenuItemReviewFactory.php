<?php

namespace Database\Factories;

use App\Models\MenuItemReview;
use App\Models\Guest;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemReviewFactory extends Factory
{
    protected $model = MenuItemReview::class;

    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'order_id' => Order::factory(),
            'menu_item_id' => MenuItem::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'review_text' => $this->faker->optional(0.8)->paragraph(),
            'status' => MenuItemReview::STATUS_PENDING,
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MenuItemReview::STATUS_PENDING,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MenuItemReview::STATUS_APPROVED,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MenuItemReview::STATUS_REJECTED,
            'rejected_by' => User::factory(),
            'rejected_at' => now(),
        ]);
    }
}
