<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\MenuItemReview;
use App\Models\Guest;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\RatingCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
class MenuItemReviewObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that rating recalculation is triggered when review status changes to approved.
     */
    public function test_it_triggers_rating_recalculation_when_status_changes_to_approved(): void
    {
        // Mock the RatingCalculationService
        $mockService = Mockery::mock(RatingCalculationService::class);
        $this->app->instance(RatingCalculationService::class, $mockService);

        // Create test data
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        $moderator = User::factory()->create(['role' => 'manager']);

        // Create a pending review
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Expect recalculateForMenuItem to be called once with the correct menu item ID
        $mockService->shouldReceive('recalculateForMenuItem')
            ->once()
            ->with($menuItem->id);

        // Update review status to approved - this should trigger the observer
        $review->update([
            'status' => MenuItemReview::STATUS_APPROVED,
            'approved_by' => $moderator->id,
            'approved_at' => now(),
        ]);

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    /**
     * Test that rating recalculation is triggered when review status changes from approved.
     */
    public function test_it_triggers_rating_recalculation_when_status_changes_from_approved(): void
    {
        // Mock the RatingCalculationService
        $mockService = Mockery::mock(RatingCalculationService::class);
        $this->app->instance(RatingCalculationService::class, $mockService);

        // Create test data
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        $moderator = User::factory()->create(['role' => 'manager']);

        // Create an approved review
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
            'approved_by' => $moderator->id,
            'approved_at' => now(),
        ]);

        // Expect recalculateForMenuItem to be called once
        $mockService->shouldReceive('recalculateForMenuItem')
            ->once()
            ->with($menuItem->id);

        // Update review status to rejected - this should trigger the observer
        $review->update([
            'status' => MenuItemReview::STATUS_REJECTED,
            'rejected_by' => $moderator->id,
            'rejected_at' => now(),
        ]);

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    /**
     * Test that rating recalculation is NOT triggered when status changes between non-approved states.
     */
    public function test_it_does_not_trigger_recalculation_when_status_changes_between_pending_and_rejected(): void
    {
        // Mock the RatingCalculationService
        $mockService = Mockery::mock(RatingCalculationService::class);
        $this->app->instance(RatingCalculationService::class, $mockService);

        // Create test data
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        $moderator = User::factory()->create(['role' => 'manager']);

        // Create a pending review
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Expect recalculateForMenuItem to NOT be called
        $mockService->shouldNotReceive('recalculateForMenuItem');

        // Update review status to rejected (from pending, not through approved)
        $review->update([
            'status' => MenuItemReview::STATUS_REJECTED,
            'rejected_by' => $moderator->id,
            'rejected_at' => now(),
        ]);

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    /**
     * Test that rating recalculation is NOT triggered when updating fields other than status.
     */
    public function test_it_does_not_trigger_recalculation_when_updating_non_status_fields(): void
    {
        // Mock the RatingCalculationService
        $mockService = Mockery::mock(RatingCalculationService::class);
        $this->app->instance(RatingCalculationService::class, $mockService);

        // Create test data
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        // Create a pending review
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'review_text' => 'Great food!',
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Expect recalculateForMenuItem to NOT be called
        $mockService->shouldNotReceive('recalculateForMenuItem');

        // Update review text without changing status
        $review->update([
            'review_text' => 'Updated: Great food!',
        ]);

        // Update helpful count without changing status
        $review->update([
            'helpful_count' => 5,
        ]);

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    /**
     * Test integration: verify that approved reviews affect the calculated rating.
     */
    public function test_it_integrates_with_rating_calculation_service_correctly(): void
    {
        // Use real service for integration test
        $service = app(RatingCalculationService::class);

        // Create test data
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        $moderator = User::factory()->create(['role' => 'manager']);

        // Create a pending review
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Check initial stats - should not include pending review
        $initialStats = $service->calculateRatingStats($menuItem->id);
        $this->assertNull($initialStats['average_rating']);
        $this->assertEquals(0, $initialStats['review_count']);

        // Approve the review - observer should trigger recalculation
        $review->update([
            'status' => MenuItemReview::STATUS_APPROVED,
            'approved_by' => $moderator->id,
            'approved_at' => now(),
        ]);

        // Clear cache to get fresh calculation
        $service->invalidateCache($menuItem->id);

        // Check updated stats - should now include approved review
        $updatedStats = $service->calculateRatingStats($menuItem->id);
        $this->assertEquals(5.0, $updatedStats['average_rating']);
        $this->assertEquals(1, $updatedStats['review_count']);
    }
}
