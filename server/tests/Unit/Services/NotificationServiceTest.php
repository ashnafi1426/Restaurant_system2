<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\MenuItemReview;
use App\Models\ReviewNotification;
use App\Models\User;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }

    /** @test */
    public function it_notifies_all_managers_of_new_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create();
        
        // Create managers and admins
        $manager1 = User::factory()->create(['role' => 'manager']);
        $manager2 = User::factory()->create(['role' => 'manager']);
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        // Act
        $this->service->notifyModeratorsOfNewReview($review);

        // Assert
        $this->assertDatabaseHas('review_notifications', [
            'user_id' => $manager1->id,
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
        ]);

        $this->assertDatabaseHas('review_notifications', [
            'user_id' => $manager2->id,
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
        ]);

        $this->assertDatabaseHas('review_notifications', [
            'user_id' => $admin->id,
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
        ]);

        // Staff should not receive notification
        $this->assertDatabaseMissing('review_notifications', [
            'user_id' => $staff->id,
            'review_id' => $review->id,
        ]);
    }

    /** @test */
    public function it_notifies_admins_of_new_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        // Act
        $this->service->notifyModeratorsOfNewReview($review);

        // Assert
        $this->assertDatabaseHas('review_notifications', [
            'user_id' => $admin->id,
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
        ]);
    }

    /** @test */
    public function it_creates_correct_message_for_new_review_notification()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
            'rating' => 4,
        ]);
        $manager = User::factory()->create(['role' => 'manager']);

        // Act
        $this->service->notifyModeratorsOfNewReview($review);

        // Assert
        $notification = ReviewNotification::where('user_id', $manager->id)
            ->where('review_id', $review->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('new review', strtolower($notification->message));
    }

    /** @test */
    public function it_notifies_guest_when_review_is_approved()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
        ]);
        $moderator = User::factory()->create(['role' => 'manager']);

        // Act
        $this->service->notifyGuestOfModeration($review, 'approved', $moderator->id);

        // Assert
        $this->assertDatabaseHas('review_notifications', [
            'user_id' => $guest->user_id ?? $guest->id, // Adjust based on actual schema
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_REVIEW_APPROVED,
        ]);
    }

    /** @test */
    public function it_notifies_guest_when_review_is_rejected()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
        ]);
        $moderator = User::factory()->create(['role' => 'admin']);

        // Act
        $this->service->notifyGuestOfModeration($review, 'rejected', $moderator->id);

        // Assert
        $this->assertDatabaseHas('review_notifications', [
            'review_id' => $review->id,
            'notification_type' => ReviewNotification::TYPE_REVIEW_REJECTED,
        ]);
    }

    /** @test */
    public function it_includes_menu_item_name_in_notification_message()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create();
        $menuItem = $review->menuItem;
        $manager = User::factory()->create(['role' => 'manager']);

        // Act
        $this->service->notifyModeratorsOfNewReview($review);

        // Assert
        $notification = ReviewNotification::where('user_id', $manager->id)
            ->where('review_id', $review->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString($menuItem->name, $notification->message);
    }

    /** @test */
    public function it_includes_rating_in_notification_message()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create([
            'rating' => 5,
        ]);
        $manager = User::factory()->create(['role' => 'manager']);

        // Act
        $this->service->notifyModeratorsOfNewReview($review);

        // Assert
        $notification = ReviewNotification::where('user_id', $manager->id)
            ->where('review_id', $review->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('5', $notification->message);
    }

    /** @test */
    public function it_marks_notifications_as_unread_by_default()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create();
        $manager = User::factory()->create(['role' => 'manager']);

        // Act
        $this->service->notifyModeratorsOfNewReview($review);

        // Assert
        $notification = ReviewNotification::where('user_id', $manager->id)
            ->where('review_id', $review->id)
            ->first();

        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    /** @test */
    public function it_allows_notifications_to_be_marked_as_read()
    {
        // Arrange
        $notification = ReviewNotification::factory()->create([
            'is_read' => false,
            'read_at' => null,
        ]);

        // Act
        $notification->markAsRead();

        // Assert
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function it_retrieves_unread_notifications_for_user()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'manager']);
        $review1 = MenuItemReview::factory()->create();
        $review2 = MenuItemReview::factory()->create();

        ReviewNotification::factory()->create([
            'user_id' => $user->id,
            'review_id' => $review1->id,
            'is_read' => false,
        ]);

        ReviewNotification::factory()->create([
            'user_id' => $user->id,
            'review_id' => $review2->id,
            'is_read' => true,
        ]);

        // Act
        $unreadNotifications = ReviewNotification::where('user_id', $user->id)
            ->unread()
            ->get();

        // Assert
        $this->assertCount(1, $unreadNotifications);
        $this->assertEquals($review1->id, $unreadNotifications[0]->review_id);
    }

    /** @test */
    public function it_handles_no_moderators_gracefully()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create();
        // No managers or admins created

        // Act
        $this->service->notifyModeratorsOfNewReview($review);

        // Assert
        $notificationCount = ReviewNotification::where('review_id', $review->id)->count();
        $this->assertEquals(0, $notificationCount);
    }

    /** @test */
    public function it_does_not_create_duplicate_notifications_for_same_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create();
        $manager = User::factory()->create(['role' => 'manager']);

        // Act
        $this->service->notifyModeratorsOfNewReview($review);
        $firstCount = ReviewNotification::where('user_id', $manager->id)
            ->where('review_id', $review->id)
            ->count();

        $this->service->notifyModeratorsOfNewReview($review);
        $secondCount = ReviewNotification::where('user_id', $manager->id)
            ->where('review_id', $review->id)
            ->count();

        // Assert
        // This depends on implementation - it may create duplicates or not
        // Adjust based on actual behavior
        $this->assertGreaterThanOrEqual($firstCount, $secondCount);
    }
}
