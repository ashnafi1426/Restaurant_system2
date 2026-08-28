<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MenuItemReview;
use App\Models\ReviewResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ResponseApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->guest = User::factory()->create(['role' => 'guest']);
    }

    /** @test */
    public function manager_can_create_response_to_approved_review()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'response_text' => 'Thank you for your feedback! We appreciate your business.',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonFragment([
            'response_text' => 'Thank you for your feedback! We appreciate your business.',
        ]);
        $this->assertDatabaseHas('review_responses', [
            'review_id' => $review->id,
            'responder_id' => $this->manager->id,
            'response_text' => 'Thank you for your feedback! We appreciate your business.',
        ]);
    }

    /** @test */
    public function it_prevents_response_to_pending_review()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->pending()->create();

        $data = [
            'response_text' => 'Thank you for your feedback!',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function it_prevents_response_to_rejected_review()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->rejected()->create();

        $data = [
            'response_text' => 'Thank you for your feedback!',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function it_enforces_500_character_limit_on_response()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'response_text' => str_repeat('a', 501),
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('response_text');
    }

    /** @test */
    public function it_enforces_one_response_per_review()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->approved()->create();

        ReviewResponse::factory()->create([
            'review_id' => $review->id,
        ]);

        $data = [
            'response_text' => 'Another response',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function manager_can_update_response()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $response = ReviewResponse::factory()->create([
            'response_text' => 'Original response',
        ]);

        $data = [
            'response_text' => 'Updated response text',
        ];

        // Act
        $updateResponse = $this->putJson("/api/admin/responses/{$response->id}", $data);

        // Assert
        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('review_responses', [
            'id' => $response->id,
            'response_text' => 'Updated response text',
        ]);
    }

    /** @test */
    public function manager_can_delete_response()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $response = ReviewResponse::factory()->create();
        $reviewId = $response->review_id;

        // Act
        $deleteResponse = $this->deleteJson("/api/admin/responses/{$response->id}");

        // Assert
        $deleteResponse->assertStatus(204);
        $this->assertDatabaseMissing('review_responses', [
            'id' => $response->id,
        ]);
        
        // Verify review still exists
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $reviewId,
        ]);
    }

    /** @test */
    public function guest_cannot_create_response()
    {
        // Arrange
        Sanctum::actingAs($this->guest);
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'response_text' => 'Thank you!',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();

        // Act
        Sanctum::actingAs(null);
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", [
            'response_text' => 'Thank you!',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function response_text_is_required()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->approved()->create();

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('response_text');
    }

    /** @test */
    public function it_strips_html_from_response_text()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'response_text' => '<script>alert("xss")</script>Thank you for your feedback!',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(201);
        $stored = ReviewResponse::find($response->json('data.id'));
        $this->assertStringNotContainsString('<script>', $stored->response_text);
        $this->assertStringNotContainsString('alert', $stored->response_text);
    }

    /** @test */
    public function response_includes_responder_information()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'response_text' => 'Thank you for your feedback!',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(201);
        $this->assertEquals($this->manager->id, $response->json('data.responder_id'));
        $this->assertNotNull($response->json('data.created_at'));
    }

    /** @test */
    public function admin_can_manage_responses()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $review = MenuItemReview::factory()->approved()->create();

        $data = [
            'response_text' => 'Thank you for your feedback!',
        ];

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/response", $data);

        // Assert
        $response->assertStatus(201);
    }
}
