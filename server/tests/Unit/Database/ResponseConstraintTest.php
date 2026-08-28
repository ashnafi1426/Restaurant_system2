<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\ReviewResponse;
use App\Models\MenuItemReview;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResponseConstraintTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_enforces_unique_constraint_on_review_id()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $responder = User::factory()->create();

        ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_review_id()
    {
        // Arrange
        $responder = User::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewResponse::factory()->create([
            'review_id' => 'nonexistent-review-id',
            'responder_id' => $responder->id,
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_responder_id()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => 'nonexistent-user-id',
        ]);
    }

    /** @test */
    public function it_cascade_deletes_responses_when_review_is_deleted()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $response = ReviewResponse::factory()->create([
            'review_id' => $review->id,
        ]);

        // Act
        $review->delete();

        // Assert
        $this->assertDatabaseMissing('review_responses', [
            'id' => $response->id,
        ]);
    }

    /** @test */
    public function it_allows_null_responder_id_on_cascade_delete()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $responder = User::factory()->create();
        $response = ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
        ]);

        // Act
        $responder->delete();

        // Assert - Response should still exist with null responder_id
        $response->refresh();
        $this->assertNull($response->responder_id);
    }

    /** @test */
    public function it_allows_different_reviews_to_have_different_responses()
    {
        // Arrange
        $review1 = MenuItemReview::factory()->create();
        $review2 = MenuItemReview::factory()->create();
        $responder1 = User::factory()->create();
        $responder2 = User::factory()->create();

        // Act
        $response1 = ReviewResponse::factory()->create([
            'review_id' => $review1->id,
            'responder_id' => $responder1->id,
        ]);

        $response2 = ReviewResponse::factory()->create([
            'review_id' => $review2->id,
            'responder_id' => $responder2->id,
        ]);

        // Assert
        $this->assertNotEquals($response1->id, $response2->id);
        $this->assertDatabaseHas('review_responses', ['id' => $response1->id]);
        $this->assertDatabaseHas('review_responses', ['id' => $response2->id]);
    }

    /** @test */
    public function it_stores_response_text_correctly()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $responder = User::factory()->create();
        $responseText = 'Thank you for your feedback!';

        // Act
        $response = ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
            'response_text' => $responseText,
        ]);

        // Assert
        $this->assertEquals($responseText, $response->response_text);
        $this->assertDatabaseHas('review_responses', [
            'id' => $response->id,
            'response_text' => $responseText,
        ]);
    }

    /** @test */
    public function it_enforces_max_length_on_response_text()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $responder = User::factory()->create();
        $tooLongText = str_repeat('a', 501);

        // Act & Assert - Response text has max length of 500
        $this->expectException(QueryException::class);
        ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
            'response_text' => $tooLongText,
        ]);
    }

    /** @test */
    public function it_allows_response_text_of_exactly_500_characters()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $responder = User::factory()->create();
        $text = str_repeat('a', 500);

        // Act
        $response = ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
            'response_text' => $text,
        ]);

        // Assert
        $this->assertEquals(500, strlen($response->response_text));
        $this->assertDatabaseHas('review_responses', [
            'id' => $response->id,
        ]);
    }

    /** @test */
    public function it_tracks_created_and_updated_timestamps()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $responder = User::factory()->create();

        // Act
        $response = ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
        ]);

        // Assert
        $this->assertNotNull($response->created_at);
        $this->assertNotNull($response->updated_at);
    }

    /** @test */
    public function it_allows_same_responder_to_respond_to_different_reviews()
    {
        // Arrange
        $review1 = MenuItemReview::factory()->create();
        $review2 = MenuItemReview::factory()->create();
        $responder = User::factory()->create();

        // Act
        $response1 = ReviewResponse::factory()->create([
            'review_id' => $review1->id,
            'responder_id' => $responder->id,
        ]);

        $response2 = ReviewResponse::factory()->create([
            'review_id' => $review2->id,
            'responder_id' => $responder->id,
        ]);

        // Assert
        $this->assertNotEquals($response1->id, $response2->id);
        $this->assertEquals($responder->id, $response1->responder_id);
        $this->assertEquals($responder->id, $response2->responder_id);
    }

    /** @test */
    public function it_sets_foreign_key_to_null_on_responder_delete()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $responder = User::factory()->create();
        $response = ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
        ]);

        // Act
        $responder->delete();

        // Assert
        $response->refresh();
        $this->assertNull($response->responder_id);
        $this->assertDatabaseHas('review_responses', [
            'id' => $response->id,
            'responder_id' => null,
        ]);
    }
}
