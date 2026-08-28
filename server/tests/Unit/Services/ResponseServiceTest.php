<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ResponseService;
use App\Models\MenuItemReview;
use App\Models\ReviewResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResponseService();
    }

    /** @test */
    public function it_creates_a_response_for_approved_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $responder = User::factory()->create(['role' => 'manager']);
        $responseText = 'Thank you for your feedback!';

        // Act
        $response = $this->service->createResponse($review->id, $responder->id, $responseText);

        // Assert
        $this->assertInstanceOf(ReviewResponse::class, $response);
        $this->assertEquals($review->id, $response->review_id);
        $this->assertEquals($responder->id, $response->responder_id);
        $this->assertEquals($responseText, $response->response_text);
        $this->assertDatabaseHas('review_responses', [
            'review_id' => $review->id,
            'responder_id' => $responder->id,
            'response_text' => $responseText,
        ]);
    }

    /** @test */
    public function it_enforces_one_response_per_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $responder = User::factory()->create();

        ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
            'response_text' => 'First response',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->createResponse($review->id, $responder->id, 'Second response');
    }

    /** @test */
    public function it_updates_an_existing_response()
    {
        // Arrange
        $response = ReviewResponse::factory()->create([
            'response_text' => 'Original response',
        ]);
        $newText = 'Updated response text';

        // Act
        $updated = $this->service->updateResponse($response->id, $newText);

        // Assert
        $this->assertEquals($newText, $updated->response_text);
        $this->assertDatabaseHas('review_responses', [
            'id' => $response->id,
            'response_text' => $newText,
        ]);
    }

    /** @test */
    public function it_deletes_a_response()
    {
        // Arrange
        $response = ReviewResponse::factory()->create();
        $responseId = $response->id;

        // Act
        $result = $this->service->deleteResponse($responseId);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('review_responses', [
            'id' => $responseId,
        ]);
    }

    /** @test */
    public function it_preserves_review_when_deleting_response()
    {
        // Arrange
        $response = ReviewResponse::factory()->create();
        $reviewId = $response->review_id;

        // Act
        $this->service->deleteResponse($response->id);

        // Assert
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $reviewId,
        ]);
    }

    /** @test */
    public function it_validates_response_text_length()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $responder = User::factory()->create();
        $tooLongText = str_repeat('a', 501); // Exceeds 500 character limit

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->createResponse($review->id, $responder->id, $tooLongText);
    }

    /** @test */
    public function it_retrieves_response_for_review()
    {
        // Arrange
        $response = ReviewResponse::factory()->create();
        $review = $response->review;

        // Act
        $retrievedResponse = $review->response;

        // Assert
        $this->assertNotNull($retrievedResponse);
        $this->assertEquals($response->id, $retrievedResponse->id);
        $this->assertEquals($response->response_text, $retrievedResponse->response_text);
    }

    /** @test */
    public function it_returns_null_for_review_without_response()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();

        // Act
        $response = $review->response;

        // Assert
        $this->assertNull($response);
    }

    /** @test */
    public function it_allows_responder_information_to_be_retrieved()
    {
        // Arrange
        $responder = User::factory()->create(['role' => 'admin']);
        $response = ReviewResponse::factory()->create([
            'responder_id' => $responder->id,
        ]);

        // Act
        $retrievedResponder = $response->responder;

        // Assert
        $this->assertNotNull($retrievedResponder);
        $this->assertEquals($responder->id, $retrievedResponder->id);
        $this->assertEquals('admin', $retrievedResponder->role);
    }

    /** @test */
    public function it_updates_timestamp_when_response_is_updated()
    {
        // Arrange
        $response = ReviewResponse::factory()->create([
            'response_text' => 'Original',
        ]);
        $originalUpdatedAt = $response->updated_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Act
        $this->service->updateResponse($response->id, 'Updated text');

        // Assert
        $response->refresh();
        $this->assertGreaterThan($originalUpdatedAt, $response->updated_at);
    }

    /** @test */
    public function it_handles_special_characters_in_response_text()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();
        $responder = User::factory()->create();
        $specialText = 'Thank you! We appreciate your feedback & will improve. @Service Team';

        // Act
        $response = $this->service->createResponse($review->id, $responder->id, $specialText);

        // Assert
        $this->assertEquals($specialText, $response->response_text);
    }

    /** @test */
    public function it_allows_empty_response_list_for_multiple_reviews()
    {
        // Arrange
        $review1 = MenuItemReview::factory()->approved()->create();
        $review2 = MenuItemReview::factory()->approved()->create();

        ReviewResponse::factory()->create(['review_id' => $review1->id]);

        // Act
        $response1 = $review1->response;
        $response2 = $review2->response;

        // Assert
        $this->assertNotNull($response1);
        $this->assertNull($response2);
    }
}
