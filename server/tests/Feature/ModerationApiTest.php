<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MenuItemReview;
use App\Models\ReviewNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ModerationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $admin;
    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->guest = User::factory()->create(['role' => 'guest']);
    }

    /** @test */
    public function manager_can_view_all_reviews()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        MenuItemReview::factory()->pending()->createMany(3);
        MenuItemReview::factory()->approved()->createMany(2);

        // Act
        $response = $this->getJson('/api/admin/reviews');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'rating', 'status'],
            ],
            'total',
            'per_page',
        ]);
    }

    /** @test */
    public function it_filters_reviews_by_status()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        MenuItemReview::factory()->pending()->createMany(3);
        MenuItemReview::factory()->approved()->createMany(2);

        // Act
        $response = $this->getJson('/api/admin/reviews?status=pending');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
        $this->assertTrue($response->json('data.0.status') === MenuItemReview::STATUS_PENDING);
    }

    /** @test */
    public function it_paginates_reviews()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        MenuItemReview::factory()->pending()->createMany(25);

        // Act
        $page1 = $this->getJson('/api/admin/reviews?per_page=10&page=1');
        $page2 = $this->getJson('/api/admin/reviews?per_page=10&page=2');

        // Assert
        $page1->assertStatus(200);
        $page2->assertStatus(200);
        $this->assertCount(10, $page1->json('data'));
        $this->assertCount(10, $page2->json('data'));
        $this->assertEquals(25, $page1->json('total'));
    }

    /** @test */
    public function manager_can_approve_review()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->pending()->create();

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/approve");

        // Assert
        $response->assertStatus(200);
        $review->refresh();
        $this->assertTrue($review->isApproved());
        $this->assertEquals($this->manager->id, $review->approved_by);
    }

    /** @test */
    public function manager_can_reject_review()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->pending()->create();

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/reject");

        // Assert
        $response->assertStatus(200);
        $review->refresh();
        $this->assertTrue($review->isRejected());
        $this->assertEquals($this->manager->id, $review->rejected_by);
    }

    /** @test */
    public function admin_can_permanently_delete_review()
    {
        // Arrange
        Sanctum::actingAs($this->admin);
        $review = MenuItemReview::factory()->create();

        // Act
        $response = $this->deleteJson("/api/admin/reviews/{$review->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('menu_item_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function guest_cannot_access_moderation_endpoints()
    {
        // Arrange
        Sanctum::actingAs($this->guest);
        $review = MenuItemReview::factory()->pending()->create();

        // Act
        $response = $this->getJson('/api/admin/reviews');

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function approval_creates_notification_for_guest()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->pending()->create();

        // Act
        $this->postJson("/api/admin/reviews/{$review->id}/approve");

        // Assert
        $this->assertDatabaseHas('review_notifications', [
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_REVIEW_APPROVED,
        ]);
    }

    /** @test */
    public function rejection_creates_notification_for_guest()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->pending()->create();

        // Act
        $this->postJson("/api/admin/reviews/{$review->id}/reject");

        // Assert
        $this->assertDatabaseHas('review_notifications', [
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_REVIEW_REJECTED,
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_moderation()
    {
        // Act
        Sanctum::actingAs(null);
        $response = $this->getJson('/api/admin/reviews');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_review_details_with_guest_info()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->pending()->create();

        // Act
        $response = $this->getJson('/api/admin/reviews?status=pending');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'rating', 'review_text', 'status', 'guest_id'],
            ],
        ]);
    }

    /** @test */
    public function it_prevents_approving_already_approved_review()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        $review = MenuItemReview::factory()->approved()->create();

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/approve");

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function admin_role_can_moderate()
    {
        // Arrange
        Sanctum::actingAs($this->admin);
        $review = MenuItemReview::factory()->pending()->create();

        // Act
        $response = $this->postJson("/api/admin/reviews/{$review->id}/approve");

        // Assert
        $response->assertStatus(200);
        $review->refresh();
        $this->assertTrue($review->isApproved());
    }

    /** @test */
    public function it_returns_correct_pagination_meta()
    {
        // Arrange
        Sanctum::actingAs($this->manager);
        MenuItemReview::factory()->pending()->createMany(15);

        // Act
        $response = $this->getJson('/api/admin/reviews?per_page=5&page=2');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(15, $response->json('total'));
        $this->assertEquals(5, $response->json('per_page'));
        $this->assertEquals(2, $response->json('current_page'));
    }
}
