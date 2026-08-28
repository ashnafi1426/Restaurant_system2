<?php

namespace Database\Factories;

use App\Models\ReviewResponse;
use App\Models\MenuItemReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewResponseFactory extends Factory
{
    protected $model = ReviewResponse::class;

    public function definition(): array
    {
        return [
            'review_id' => MenuItemReview::factory(),
            'responder_id' => User::factory(),
            'response_text' => $this->faker->text(500),
        ];
    }
}
