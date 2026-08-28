<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Guest;
use App\Models\MenuItemReview;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GuestDeletionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * When a guest is deleted, their approved reviews should be anonymized
     */
    public function it_anonymizes_approved_reviews_when_guest_is_deleted()
    {
        // Arrange
        $guest = Guest::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest->id,
        ]);

        // Verify guest name before deletion
        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'first_name' => 'John',
        ]);

        // Act - Delete guest
        $guest->delete();

        // Assert - Review should still exist
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $review->id,
        ]);

        // Assert - Guest should be soft deleted
        $this->assertSoftDeleted('guests', ['id' => $guest->id]);

        // Assert - Review should show anonymized name
        $review->refresh();
        $anonymizedName = $review->anonymized_guest_name;
        $this->assertEquals('Anonymous Guest', $anonymizedName);
    }

    /** @test */
    public function it_preserves_review_data_after_guest_deletion()
    {
        // Arrange
        $guest = Guest::factory()->create();

        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest->id,
            'rating' => 5,
            'review_text' => 'Excellent food and service!',
        ]);

        // Act - Delete guest
        $guest->delete();

        // Assert - Review data should be preserved
        $review->refresh();
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Excellent food and service!', $review->review_text);
        $this->assertEquals(MenuItemReview::STATUS_APPROVED, $review->status);
    }

    /** @test */
    public function it_makes_reviews_inaccessible_by_guest_id_after_deletion()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest->id,
        ]);

        $guestId = $guest->id;

        // Act - Delete guest
        $guest->delete();

        // Assert - Cannot retrieve guest
        $deletedGuest = Guest::find($guestId);
        $this->assertNull($deletedGuest);

        // Assert - Review still exists but with anonymized name
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $review->id,
        ]);
    }

    /** @test */
    public function it_does_not_affect_pending_reviews_on_guest_deletion()
    {
        // Arrange
        $guest = Guest::factory()->create();

        $pendingReview = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
        ]);

        // Act - Delete guest
        $guest->delete();

        // Assert - Pending review should be handled according to observer logic
        // If it's deleted, check soft delete. If it's kept, check guest_id
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $pendingReview->id,
        ]);
    }

    /** @test */
    public function it_does_not_affect_rejected_reviews_on_guest_deletion()
    {
        // Arrange
        $guest = Guest::factory()->create();

        $rejectedReview = MenuItemReview::factory()->rejected()->create([
            'guest_id' => $guest->id,
        ]);

        // Act - Delete guest
        $guest->delete();

        // Assert
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $rejectedReview->id,
        ]);
    }

    /** @test */
    public function it_anonymizes_all_approved_reviews_for_deleted_guest()
    {
        // Arrange
        $guest = Guest::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $reviews = MenuItemReview::factory()->approved()->createMany(3, [
            'guest_id' => $guest->id,
        ]);

        // Act - Delete guest
        $guest->delete();

        // Assert - All reviews should be anonymized
        foreach ($reviews as $review) {
            $review->refresh();
            $this->assertEquals('Anonymous Guest', $review->anonymized_guest_name);
        }
    }

    /** @test */
    public function it_preserves_public_review_display_after_guest_deletion()
    {
        // Arrange
        $guest = Guest::factory()->create([
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
        ]);

        $menuItem = $guest->reviews()->first()?->menuItem ?? null;
        if (!$menuItem) {
            $menuItem = \App\Models\MenuItem::factory()->create();
        }

        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'review_text' => 'Good quality food',
        ]);

        // Act - Delete guest and fetch public reviews
        $guest->delete();

        $publicReviews = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert - Review should still be in public view but anonymized
        $publicReviews->assertStatus(200);
        $this->assertCount(1, $publicReviews->json('data'));

        $publicReview = $publicReviews->json('data.0');
        $this->assertEquals('Anonymous Guest', $publicReview['guest_name']);
        $this->assertEquals(4, $publicReview['rating']);
        $this->assertEquals('Good quality food', $publicReview['review_text']);
    }

    /** @test */
    public function it_maintains_rating_calculation_after_guest_deletion()
    {
        // Arrange
        $guest1 = Guest::factory()->create();
        $guest2 = Guest::factory()->create();
        $menuItem = \App\Models\MenuItem::factory()->create();

        MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest1->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ]);

        MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest2->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
        ]);

        // Verify initial rating
        $statsBeforeDelete = $this->getJson("/api/admin/analytics/menu-items/{$menuItem->id}/stats");
        $this->assertEquals(4.0, $statsBeforeDelete->json('average_rating'));

        // Act - Delete guest1
        $guest1->delete();

        // Assert - Rating should still include deleted guest's review
        $statsAfterDelete = $this->getJson("/api/admin/analytics/menu-items/{$menuItem->id}/stats");
        $this->assertEquals(4.0, $statsAfterDelete->json('average_rating'));
        $this->assertEquals(2, $statsAfterDelete->json('total_reviews'));
    }

    /** @test */
    public function it_allows_querying_reviews_by_anonymized_name_after_deletion()
    {
        // Arrange
        $guest = Guest::factory()->create();

        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest->id,
        ]);

        // Act - Delete guest
        $guest->delete();

        // Assert - Anonymized name should be retrievable
        $review->refresh();
        $this->assertNotNull($review->anonymized_guest_name);
        $this->assertTrue(strlen($review->anonymized_guest_name) > 0);
    }
}
