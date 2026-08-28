<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\VotingService;
use App\Models\MenuItemReview;
use App\Models\ReviewHelpfulnessVote;
use App\Models\Guest;
use App\Exceptions\DuplicateVoteException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VotingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VotingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VotingService();
    }

    /** @test */
    public function it_records_a_helpful_vote_successfully()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        // Act
        $this->service->voteHelpful($review->id, $guest->id, null);

        // Assert
        $this->assertDatabaseHas('review_helpfulness_votes', [
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        $review->refresh();
        $this->assertEquals(1, $review->helpful_count);
    }

    /** @test */
    public function it_records_a_not_helpful_vote_successfully()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        // Act
        $this->service->voteNotHelpful($review->id, $guest->id, null);

        // Assert
        $this->assertDatabaseHas('review_helpfulness_votes', [
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_NOT_HELPFUL,
        ]);

        $review->refresh();
        $this->assertEquals(1, $review->not_helpful_count);
    }

    /** @test */
    public function it_prevents_duplicate_helpful_vote_from_same_guest()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        $this->service->voteHelpful($review->id, $guest->id, null);

        // Act & Assert
        $this->expectException(DuplicateVoteException::class);
        $this->service->voteHelpful($review->id, $guest->id, null);
    }

    /** @test */
    public function it_prevents_duplicate_not_helpful_vote_from_same_guest()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        $this->service->voteNotHelpful($review->id, $guest->id, null);

        // Act & Assert
        $this->expectException(DuplicateVoteException::class);
        $this->service->voteNotHelpful($review->id, $guest->id, null);
    }

    /** @test */
    public function it_prevents_guest_from_voting_twice_on_same_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest = Guest::factory()->create();

        $this->service->voteHelpful($review->id, $guest->id, null);

        // Act & Assert
        $this->expectException(DuplicateVoteException::class);
        $this->service->voteNotHelpful($review->id, $guest->id, null);
    }

    /** @test */
    public function it_allows_anonymous_voting_with_ip_address()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $ipAddress = '192.168.1.1';

        // Act
        $this->service->voteHelpful($review->id, null, $ipAddress);

        // Assert
        $this->assertDatabaseHas('review_helpfulness_votes', [
            'review_id' => $review->id,
            'guest_id' => null,
            'ip_address' => $ipAddress,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        $review->refresh();
        $this->assertEquals(1, $review->helpful_count);
    }

    /** @test */
    public function it_prevents_duplicate_anonymous_vote_from_same_ip()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $ipAddress = '192.168.1.1';

        $this->service->voteHelpful($review->id, null, $ipAddress);

        // Act & Assert
        $this->expectException(DuplicateVoteException::class);
        $this->service->voteHelpful($review->id, null, $ipAddress);
    }

    /** @test */
    public function it_allows_different_guests_to_vote_on_same_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $guest1 = Guest::factory()->create();
        $guest2 = Guest::factory()->create();

        // Act
        $this->service->voteHelpful($review->id, $guest1->id, null);
        $this->service->voteNotHelpful($review->id, $guest2->id, null);

        // Assert
        $review->refresh();
        $this->assertEquals(1, $review->helpful_count);
        $this->assertEquals(1, $review->not_helpful_count);
    }

    /** @test */
    public function it_allows_different_ip_addresses_to_vote_on_same_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $ip1 = '192.168.1.1';
        $ip2 = '192.168.1.2';

        // Act
        $this->service->voteHelpful($review->id, null, $ip1);
        $this->service->voteNotHelpful($review->id, null, $ip2);

        // Assert
        $review->refresh();
        $this->assertEquals(1, $review->helpful_count);
        $this->assertEquals(1, $review->not_helpful_count);
    }

    /** @test */
    public function it_calculates_helpfulness_ratio_correctly()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create([
            'helpful_count' => 7,
            'not_helpful_count' => 3,
        ]);

        // Act
        $ratio = $review->helpfulness_ratio;

        // Assert
        $this->assertEquals(0.7, $ratio); // 7 / (7 + 3) = 0.7
    }

    /** @test */
    public function it_handles_zero_helpfulness_ratio()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create([
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ]);

        // Act
        $ratio = $review->helpfulness_ratio;

        // Assert
        $this->assertEquals(0, $ratio);
    }

    /** @test */
    public function it_increments_helpful_count_correctly()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create([
            'helpful_count' => 5,
        ]);
        $guest = Guest::factory()->create();

        // Act
        $this->service->voteHelpful($review->id, $guest->id, null);

        // Assert
        $review->refresh();
        $this->assertEquals(6, $review->helpful_count);
    }

    /** @test */
    public function it_increments_not_helpful_count_correctly()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create([
            'not_helpful_count' => 3,
        ]);
        $guest = Guest::factory()->create();

        // Act
        $this->service->voteNotHelpful($review->id, $guest->id, null);

        // Assert
        $review->refresh();
        $this->assertEquals(4, $review->not_helpful_count);
    }
}
