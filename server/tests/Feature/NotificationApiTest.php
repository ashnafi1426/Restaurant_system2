<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ReviewNotification;
use App\Models\MenuItemReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_returns_user_review_notifications()
    {
        // Arrange
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        // Create notification for another user
        $otherUser = User::factory()->create();
        ReviewNotification::factory()->create([
            'user_id' => $otherUser->id,
            'is_read' => false,
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('total'));
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_returns_unread_notification_count()
    {
        // Arrange
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews/unread-count');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('unread_count'));
    }

    /** @test */
    public function it_marks_notification_as_read()
    {
        // Arrange
        $notification = ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
            'read_at' => null,
        ]);

        // Act
        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        // Assert
        $response->assertStatus(200);
        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function it_returns_zero_unread_count_when_all_read()
    {
        // Arrange
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews/unread-count');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('unread_count'));
    }

    /** @test */
    public function it_includes_notification_details()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();
        $notification = ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
            'message' => 'New review submitted',
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(200);
        $notif = $response->json('data.0');
        $this->assertEquals($notification->id, $notif['id']);
        $this->assertEquals($review->id, $notif['review_id']);
        $this->assertEquals('new_review', $notif['notification_type']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Act
        Sanctum::actingAs(null);
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_only_returns_user_own_notifications()
    {
        // Arrange
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $otherUser = User::factory()->create();
        ReviewNotification::factory()->create([
            'user_id' => $otherUser->id,
            'is_read' => false,
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
    }

    /** @test */
    public function it_paginates_notifications()
    {
        // Arrange
        ReviewNotification::factory()->createMany(25, [
            'user_id' => $this->user->id,
        ]);

        // Act
        $page1 = $this->getJson('/api/notifications/reviews?per_page=10&page=1');
        $page2 = $this->getJson('/api/notifications/reviews?per_page=10&page=2');

        // Assert
        $page1->assertStatus(200);
        $page2->assertStatus(200);
        $this->assertCount(10, $page1->json('data'));
        $this->assertCount(10, $page2->json('data'));
        $this->assertEquals(25, $page1->json('total'));
    }

    /** @test */
    public function it_returns_notification_timestamps()
    {
        // Arrange
        $notification = ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(200);
        $notif = $response->json('data.0');
        $this->assertNotNull($notif['created_at']);
        $this->assertNotNull($notif['read_at']);
    }

    /** @test */
    public function it_supports_sorting_by_newest_first()
    {
        // Arrange
        $old = ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $new = ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals($new->id, $response->json('data.0.id'));
        $this->assertEquals($old->id, $response->json('data.1.id'));
    }

    /** @test */
    public function it_filters_unread_notifications()
    {
        // Arrange
        ReviewNotification::factory()->createMany(3, [
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        ReviewNotification::factory()->createMany(2, [
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews?unread=true');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    /** @test */
    public function it_distinguishes_notification_types()
    {
        // Arrange
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
        ]);
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_type' => ReviewNotification::TYPE_REVIEW_APPROVED,
        ]);
        ReviewNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_type' => ReviewNotification::TYPE_REVIEW_REJECTED,
        ]);

        // Act
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
        
        $types = array_map(fn($n) => $n['notification_type'], $response->json('data'));
        $this->assertContains('new_review', $types);
        $this->assertContains('review_approved', $types);
        $this->assertContains('review_rejected', $types);
    }

    /** @test */
    public function it_returns_empty_list_when_no_notifications()
    {
        // Act
        $response = $this->getJson('/api/notifications/reviews');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
        $this->assertCount(0, $response->json('data'));
    }
}
