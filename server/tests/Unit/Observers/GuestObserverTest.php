<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Guest;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\MenuItemReview;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GuestObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that approved reviews are anonymized when a guest is deleted.
     *
     * @return void
     */
    public function test_approved_reviews_are_anonymized_when_guest_deleted(): void
    {
        // Create guest
        $guest = Guest::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Create an approved review
        $approvedReview = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Verify the review is linked to the guest
        $this->assertEquals($guest->id, $approvedReview->guest_id);

        // Delete the guest
        $guest->delete();

        // Refresh the review from database
        $approvedReview->refresh();

        // Verify guest_id is set to null (anonymized)
        $this->assertNull($approvedReview->guest_id);
    }

    /**
     * Test that pending reviews are not affected when a guest is deleted.
     *
     * @return void
     */
    public function test_pending_reviews_are_not_anonymized_when_guest_deleted(): void
    {
        // Create guest
        $guest = Guest::factory()->create();

        // Create a pending review
        $pendingReview = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Store original guest_id
        $originalGuestId = $pendingReview->guest_id;

        // Delete the guest
        $guest->delete();

        // Refresh the review from database
        $pendingReview->refresh();

        // Verify guest_id is still set (not anonymized)
        $this->assertEquals($originalGuestId, $pendingReview->guest_id);
    }

    /**
     * Test that rejected reviews are not affected when a guest is deleted.
     *
     * @return void
     */
    public function test_rejected_reviews_are_not_anonymized_when_guest_deleted(): void
    {
        // Create guest
        $guest = Guest::factory()->create();

        // Create a rejected review
        $rejectedReview = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'status' => MenuItemReview::STATUS_REJECTED,
        ]);

        // Store original guest_id
        $originalGuestId = $rejectedReview->guest_id;

        // Delete the guest
        $guest->delete();

        // Refresh the review from database
        $rejectedReview->refresh();

        // Verify guest_id is still set (not anonymized)
        $this->assertEquals($originalGuestId, $rejectedReview->guest_id);
    }

    /**
     * Test that multiple approved reviews are anonymized when a guest is deleted.
     *
     * @return void
     */
    public function test_multiple_approved_reviews_are_anonymized_when_guest_deleted(): void
    {
        // Create guest
        $guest = Guest::factory()->create();

        // Create multiple approved reviews
        $review1 = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        $review2 = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        $review3 = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Delete the guest
        $guest->delete();

        // Refresh reviews from database
        $review1->refresh();
        $review2->refresh();
        $review3->refresh();

        // Verify all reviews are anonymized
        $this->assertNull($review1->guest_id);
        $this->assertNull($review2->guest_id);
        $this->assertNull($review3->guest_id);
    }

    /**
     * Test that anonymized reviews display "Anonymous Guest" as the guest name.
     *
     * @return void
     */
    public function test_anonymized_reviews_display_anonymous_guest_name(): void
    {
        // Create guest
        $guest = Guest::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Create an approved review
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Delete the guest
        $guest->delete();

        // Refresh the review
        $review->refresh();

        // Verify the anonymized guest name accessor returns "Anonymous Guest"
        $this->assertEquals('Anonymous Guest', $review->anonymized_guest_name);
    }
}
