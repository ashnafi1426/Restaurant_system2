<?php

namespace Database\Factories;

use App\Models\ReviewNotification;
use App\Models\MenuItemReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewNotificationFactory extends Factory
{
    protected $model = ReviewNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'review_id' => MenuItemReview::factory(),
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
            'message' => $this->faker->sentence(),
            'is_read' => false,
            'read_at' => null,
        ];
    }

    public function newReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
        ]);
    }

    public function reviewApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => ReviewNotification::TYPE_REVIEW_APPROVED,
        ]);
    }

    public function reviewRejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => ReviewNotification::TYPE_REVIEW_REJECTED,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
