<?php

namespace Database\Factories;

use App\Models\ReviewHelpfulnessVote;
use App\Models\MenuItemReview;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewHelpfulnessVoteFactory extends Factory
{
    protected $model = ReviewHelpfulnessVote::class;

    public function definition(): array
    {
        return [
            'review_id' => MenuItemReview::factory(),
            'guest_id' => Guest::factory(),
            'ip_address' => $this->faker->ipv4(),
            'vote_type' => $this->faker->randomElement([
                ReviewHelpfulnessVote::VOTE_HELPFUL,
                ReviewHelpfulnessVote::VOTE_NOT_HELPFUL
            ]),
        ];
    }

    public function helpful(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);
    }

    public function notHelpful(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote_type' => ReviewHelpfulnessVote::VOTE_NOT_HELPFUL,
        ]);
    }
}
