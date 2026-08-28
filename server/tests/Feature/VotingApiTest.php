<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MenuItemReview;
use App\Models\ReviewHelpfulnessVote;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VotingApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_records_helpful_vote()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        $data = [
            'guest_id' => $guest->id,
            'vote_type' => 'helpful',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(201);
        $review->refresh();
        $this->assertEquals(1, $review->helpful_count);
        $this->assertDatabaseHas('review_helpfulness_votes', [
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);
    }

    /** @test */
    public function it_records_not_helpful_vote()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        $data = [
            'guest_id' => $guest->id,
            'vote_type' => 'not_helpful',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(201);
        $review->refresh();
        $this->assertEquals(1, $review->not_helpful_count);
        $this->assertDatabaseHas('review_helpfulness_votes', [
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_NOT_HELPFUL,
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_vote_from_same_guest()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        $data = [
            'guest_id' => $guest->id,
            'vote_type' => 'helpful',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'You have already voted on this review',
        ]);
    }

    /** @test */
    public function it_allows_anonymous_voting_with_ip_address()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $ipAddress = '192.168.1.1';

        $data = [
            'ip_address' => $ipAddress,
            'vote_type' => 'helpful',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(201);
        $review->refresh();
        $this->assertEquals(1, $review->helpful_count);
        $this->assertDatabaseHas('review_helpfulness_votes', [
            'review_id' => $review->id,
            'ip_address' => $ipAddress,
            'guest_id' => null,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_anonymous_vote_from_same_ip()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $ipAddress = '192.168.1.1';

        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'ip_address' => $ipAddress,
            'guest_id' => null,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        $data = [
            'ip_address' => $ipAddress,
            'vote_type' => 'helpful',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function it_allows_different_guests_to_vote()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest1 = Guest::factory()->create();
        $guest2 = Guest::factory()->create();

        // Act
        $response1 = $this->postJson("/api/reviews/{$review->id}/vote", [
            'guest_id' => $guest1->id,
            'vote_type' => 'helpful',
        ]);

        $response2 = $this->postJson("/api/reviews/{$review->id}/vote", [
            'guest_id' => $guest2->id,
            'vote_type' => 'helpful',
        ]);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);
        $review->refresh();
        $this->assertEquals(2, $review->helpful_count);
    }

    /** @test */
    public function it_returns_updated_vote_counts()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create([
            'helpful_count' => 5,
            'not_helpful_count' => 2,
        ]);
        $guest = Guest::factory()->create();

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", [
            'guest_id' => $guest->id,
            'vote_type' => 'helpful',
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonFragment([
            'helpful_count' => 6,
            'not_helpful_count' => 2,
        ]);
    }

    /** @test */
    public function it_validates_vote_type()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        $data = [
            'guest_id' => $guest->id,
            'vote_type' => 'neutral',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('vote_type');
    }

    /** @test */
    public function it_requires_either_guest_id_or_ip_address()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'vote_type' => 'helpful',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function it_does_not_require_authentication()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $ipAddress = '192.168.1.1';

        $data = [
            'ip_address' => $ipAddress,
            'vote_type' => 'helpful',
        ];

        // Act - No authentication
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_captures_ip_address_from_request()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'vote_type' => 'helpful',
        ];

        // Act - Should capture IP from request
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data, [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
        ]);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_allows_guest_to_switch_vote_preference()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        // Vote helpful first time
        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        // Try to vote not_helpful
        $data = [
            'guest_id' => $guest->id,
            'vote_type' => 'not_helpful',
        ];

        // Act
        $response = $this->postJson("/api/reviews/{$review->id}/vote", $data);

        // Assert - Should fail due to duplicate vote constraint
        $response->assertStatus(422);
    }

    /** @test */
    public function it_increments_correct_count_on_vote()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create([
            'helpful_count' => 10,
            'not_helpful_count' => 5,
        ]);
        $guest = Guest::factory()->create();

        // Act
        $this->postJson("/api/reviews/{$review->id}/vote", [
            'guest_id' => $guest->id,
            'vote_type' => 'not_helpful',
        ]);

        // Assert
        $review->refresh();
        $this->assertEquals(10, $review->helpful_count);
        $this->assertEquals(6, $review->not_helpful_count);
    }

    /** @test */
    public function it_allows_different_ips_to_vote_on_same_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $ip1 = '192.168.1.1';
        $ip2 = '192.168.1.2';

        // Act
        $response1 = $this->postJson("/api/reviews/{$review->id}/vote", [
            'ip_address' => $ip1,
            'vote_type' => 'helpful',
        ]);

        $response2 = $this->postJson("/api/reviews/{$review->id}/vote", [
            'ip_address' => $ip2,
            'vote_type' => 'helpful',
        ]);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);
        $review->refresh();
        $this->assertEquals(2, $review->helpful_count);
    }
}
