<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\ReviewHelpfulnessVote;
use App\Models\MenuItemReview;
use App\Models\Guest;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoteConstraintTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_enforces_unique_constraint_on_review_id_and_guest_id()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $guest = Guest::factory()->create();

        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'ip_address' => null,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'ip_address' => null,
            'vote_type' => ReviewHelpfulnessVote::VOTE_NOT_HELPFUL,
        ]);
    }

    /** @test */
    public function it_enforces_unique_constraint_on_review_id_and_ip_address()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $ipAddress = '192.168.1.1';

        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => null,
            'ip_address' => $ipAddress,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => null,
            'ip_address' => $ipAddress,
            'vote_type' => ReviewHelpfulnessVote::VOTE_NOT_HELPFUL,
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_review_id()
    {
        // Arrange
        $guest = Guest::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewHelpfulnessVote::factory()->create([
            'review_id' => 'nonexistent-review-id',
            'guest_id' => $guest->id,
            'ip_address' => null,
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_guest_id()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => 'nonexistent-guest-id',
            'ip_address' => null,
        ]);
    }

    /** @test */
    public function it_allows_null_guest_id_for_anonymous_votes()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $ipAddress = '192.168.1.1';

        // Act
        $vote = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => null,
            'ip_address' => $ipAddress,
        ]);

        // Assert
        $this->assertNull($vote->guest_id);
        $this->assertNotNull($vote->ip_address);
    }

    /** @test */
    public function it_allows_null_ip_address_for_authenticated_votes()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $guest = Guest::factory()->create();

        // Act
        $vote = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'ip_address' => null,
        ]);

        // Assert
        $this->assertNotNull($vote->guest_id);
        $this->assertNull($vote->ip_address);
    }

    /** @test */
    public function it_cascade_deletes_votes_when_review_is_deleted()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $vote = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
        ]);

        // Act
        $review->delete();

        // Assert
        $this->assertDatabaseMissing('review_helpfulness_votes', [
            'id' => $vote->id,
        ]);
    }

    /** @test */
    public function it_sets_guest_id_to_null_on_guest_delete()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $guest = Guest::factory()->create();
        $vote = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'ip_address' => null,
        ]);

        // Act
        $guest->delete();

        // Assert
        $vote->refresh();
        $this->assertNull($vote->guest_id);
    }

    /** @test */
    public function it_allows_multiple_guests_to_vote_on_same_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $guest1 = Guest::factory()->create();
        $guest2 = Guest::factory()->create();

        // Act
        $vote1 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest1->id,
            'ip_address' => null,
        ]);

        $vote2 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest2->id,
            'ip_address' => null,
        ]);

        // Assert
        $this->assertNotEquals($vote1->id, $vote2->id);
        $this->assertDatabaseHas('review_helpfulness_votes', ['id' => $vote1->id]);
        $this->assertDatabaseHas('review_helpfulness_votes', ['id' => $vote2->id]);
    }

    /** @test */
    public function it_allows_multiple_different_ips_to_vote_on_same_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $ip1 = '192.168.1.1';
        $ip2 = '192.168.1.2';

        // Act
        $vote1 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => null,
            'ip_address' => $ip1,
        ]);

        $vote2 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => null,
            'ip_address' => $ip2,
        ]);

        // Assert
        $this->assertNotEquals($vote1->id, $vote2->id);
        $this->assertDatabaseHas('review_helpfulness_votes', ['id' => $vote1->id]);
        $this->assertDatabaseHas('review_helpfulness_votes', ['id' => $vote2->id]);
    }

    /** @test */
    public function it_enforces_vote_type_enum_constraint()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $guest = Guest::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
            'ip_address' => null,
            'vote_type' => 'neutral', // Invalid vote type
        ]);
    }

    /** @test */
    public function it_accepts_valid_vote_types()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $guest1 = Guest::factory()->create();
        $guest2 = Guest::factory()->create();

        // Act
        $helpfulVote = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest1->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        $notHelpfulVote = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest2->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_NOT_HELPFUL,
        ]);

        // Assert
        $this->assertEquals(ReviewHelpfulnessVote::VOTE_HELPFUL, $helpfulVote->vote_type);
        $this->assertEquals(ReviewHelpfulnessVote::VOTE_NOT_HELPFUL, $notHelpfulVote->vote_type);
    }

    /** @test */
    public function it_stores_created_timestamp_only()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $guest = Guest::factory()->create();

        // Act
        $vote = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'guest_id' => $guest->id,
        ]);

        // Assert
        $this->assertNotNull($vote->created_at);
    }

    /** @test */
    public function it_allows_same_guest_to_vote_on_different_reviews()
    {
        // Arrange
        $review1 = MenuItemReview::factory()->create();
        $review2 = MenuItemReview::factory()->create();
        $guest = Guest::factory()->create();

        // Act
        $vote1 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review1->id,
            'guest_id' => $guest->id,
        ]);

        $vote2 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review2->id,
            'guest_id' => $guest->id,
        ]);

        // Assert
        $this->assertNotEquals($vote1->id, $vote2->id);
        $this->assertEquals($guest->id, $vote1->guest_id);
        $this->assertEquals($guest->id, $vote2->guest_id);
    }

    /** @test */
    public function it_allows_same_ip_to_vote_on_different_reviews()
    {
        // Arrange
        $review1 = MenuItemReview::factory()->create();
        $review2 = MenuItemReview::factory()->create();
        $ipAddress = '192.168.1.1';

        // Act
        $vote1 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review1->id,
            'guest_id' => null,
            'ip_address' => $ipAddress,
        ]);

        $vote2 = ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review2->id,
            'guest_id' => null,
            'ip_address' => $ipAddress,
        ]);

        // Assert
        $this->assertNotEquals($vote1->id, $vote2->id);
        $this->assertEquals($ipAddress, $vote1->ip_address);
        $this->assertEquals($ipAddress, $vote2->ip_address);
    }

    /** @test */
    public function it_indexes_review_votes_by_vote_type()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();

        // Act
        ReviewHelpfulnessVote::factory()->create([
            'review_id' => $review->id,
            'vote_type' => ReviewHelpfulnessVote::VOTE_HELPFUL,
        ]);

        // Assert - Should be able to query efficiently
        $helpfulVotes = ReviewHelpfulnessVote::where('review_id', $review->id)
            ->where('vote_type', ReviewHelpfulnessVote::VOTE_HELPFUL)
            ->get();

        $this->assertCount(1, $helpfulVotes);
    }
}
