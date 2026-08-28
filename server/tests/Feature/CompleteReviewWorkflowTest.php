<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Guest;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItemReview;
use App\Models\ReviewNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CompleteReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Complete workflow: Guest submits review → Moderator approves → Guest votes → Management responds
     */
    public function it_completes_full_review_lifecycle()
    {
        // ===== STEP 1: Guest submits a review =====
        $guest = Guest::factory()->create();
        $user = User::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        Sanctum::actingAs($user);

        $submitResponse = $this->postJson('/api/reviews', [
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'review_text' => 'Excellent service and delicious food!',
        ]);

        $submitResponse->assertStatus(201);
        $reviewId = $submitResponse->json('data.id');

        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $reviewId,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Verify notification sent to moderators
        $moderator = User::factory()->create(['role' => 'manager']);
        $this->assertDatabaseHas('review_notifications', [
            'review_id' => $reviewId,
            'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
        ]);

        // ===== STEP 2: Moderator approves the review =====
        Sanctum::actingAs($moderator);

        $approveResponse = $this->postJson("/api/admin/reviews/{$reviewId}/approve");

        $approveResponse->assertStatus(200);

        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $reviewId,
            'status' => MenuItemReview::STATUS_APPROVED,
            'approved_by' => $moderator->id,
        ]);

        // Verify rating calculation updated
        $stats = $this->getJson("/api/admin/analytics/menu-items/{$menuItem->id}/stats");
        $stats->assertStatus(200);
        $this->assertEquals(5.0, $stats->json('average_rating'));
        $this->assertEquals(1, $stats->json('total_reviews'));

        // ===== STEP 3: Guest votes on helpfulness =====
        Sanctum::actingAs(null); // No auth needed for voting

        $voteResponse = $this->postJson("/api/reviews/{$reviewId}/vote", [
            'ip_address' => '192.168.1.1',
            'vote_type' => 'helpful',
        ]);

        $voteResponse->assertStatus(201);

        $this->assertDatabaseHas('review_helpfulness_votes', [
            'review_id' => $reviewId,
            'vote_type' => 'helpful',
        ]);

        // ===== STEP 4: Management responds to review =====
        Sanctum::actingAs($moderator);

        $responseText = 'Thank you for your wonderful feedback! We look forward to serving you again.';
        $respondResponse = $this->postJson("/api/admin/reviews/{$reviewId}/response", [
            'response_text' => $responseText,
        ]);

        $respondResponse->assertStatus(201);

        $this->assertDatabaseHas('review_responses', [
            'review_id' => $reviewId,
            'responder_id' => $moderator->id,
            'response_text' => $responseText,
        ]);

        // ===== STEP 5: Verify complete data on public view =====
        $publicResponse = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        $publicResponse->assertStatus(200);
        $reviews = $publicResponse->json('data');

        $this->assertCount(1, $reviews);
        $reviewData = $reviews[0];

        $this->assertEquals(5, $reviewData['rating']);
        $this->assertEquals('Excellent service and delicious food!', $reviewData['review_text']);
        $this->assertEquals(1, $reviewData['helpful_count']);
        $this->assertNotNull($reviewData['response']);
        $this->assertEquals($responseText, $reviewData['response']['text']);
        $this->assertEquals(5.0, $publicResponse->json('average_rating'));
    }

    /** @test */
    public function it_handles_multiple_reviews_affecting_rating_calculation()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $moderator = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($moderator);

        // Create and approve multiple reviews
        for ($i = 0; $i < 3; $i++) {
            $guest = Guest::factory()->create();
            $order = Order::factory()->create([
                'guest_id' => $guest->id,
                'status' => Order::STATUS_SERVED,
            ]);

            OrderItem::factory()->create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
            ]);

            $review = MenuItemReview::factory()->pending()->create([
                'guest_id' => $guest->id,
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'rating' => 4 + $i, // Ratings: 4, 5, 6 (but 6 will fail constraint)
            ]);

            if ($i < 2) {
                $this->postJson("/api/admin/reviews/{$review->id}/approve");
            }
        }

        // Check final rating
        $stats = $this->getJson("/api/admin/analytics/menu-items/{$menuItem->id}/stats");
        $stats->assertStatus(200);

        // Should be average of 4 and 5
        $this->assertEquals(4.5, $stats->json('average_rating'));
        $this->assertEquals(2, $stats->json('total_reviews'));
    }

    /** @test */
    public function it_prevents_modification_after_approval()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $user = User::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
        ]);

        // Approve review
        Sanctum::actingAs($user);
        $moderator = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($moderator);
        $this->postJson("/api/admin/reviews/{$review->id}/approve");

        // Try to update - should fail
        Sanctum::actingAs($user);
        $updateResponse = $this->putJson("/api/reviews/{$review->id}", [
            'rating' => 5,
        ]);

        $updateResponse->assertStatus(422);
    }

    /** @test */
    public function it_tracks_all_notifications_in_workflow()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $manager = User::factory()->create(['role' => 'manager']);
        $admin = User::factory()->create(['role' => 'admin']);

        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        Sanctum::actingAs($manager);

        // Step 1: Submit review
        $response = $this->postJson('/api/reviews', [
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
        ]);

        $reviewId = $response->json('data.id');

        // Verify new review notification
        $newReviewNotifs = ReviewNotification::where('review_id', $reviewId)
            ->where('notification_type', ReviewNotification::TYPE_NEW_REVIEW)
            ->count();
        $this->assertGreaterThan(0, $newReviewNotifs);

        // Step 2: Approve review
        $this->postJson("/api/admin/reviews/{$reviewId}/approve");

        // Verify approval notification
        $this->assertDatabaseHas('review_notifications', [
            'review_id' => $reviewId,
            'notification_type' => ReviewNotification::TYPE_REVIEW_APPROVED,
        ]);
    }
}
