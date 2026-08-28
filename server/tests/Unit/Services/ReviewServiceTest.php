<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ReviewService;
use App\Services\PurchaseVerificationService;
use App\Services\NotificationService;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\MenuItemReview;
use App\Exceptions\PurchaseNotVerifiedException;
use App\Exceptions\DuplicateReviewException;
use App\Exceptions\ReviewNotModifiableException;
use App\Exceptions\UnauthorizedReviewAccessException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReviewService $service;
    protected PurchaseVerificationService $purchaseVerificationService;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->purchaseVerificationService = new PurchaseVerificationService();
        $this->notificationService = new NotificationService();
        $this->service = new ReviewService(
            $this->purchaseVerificationService,
            $this->notificationService
        );
    }

    /** @test */
    public function it_creates_a_review_successfully_with_valid_purchase()
    {
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $review = $this->service->createReview([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'review_text' => 'Excellent food and quick delivery!',
        ]);

        $this->assertInstanceOf(MenuItemReview::class, $review);
        $this->assertEquals(MenuItemReview::STATUS_PENDING, $review->status);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Excellent food and quick delivery!', $review->review_text);
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $review->id,
            'guest_id' => $guest->id,
            'menu_item_id' => $menuItem->id,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function it_fails_to_create_review_when_guest_has_not_ordered_item()
    {
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $otherItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $otherItem->id,
        ]);

        $this->expectException(PurchaseNotVerifiedException::class);
        $this->service->createReview([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
        ]);
    }

    /** @test */
    public function it_fails_to_create_review_when_order_is_not_completed()
    {
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_PENDING,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->expectException(PurchaseNotVerifiedException::class);
        $this->service->createReview([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
        ]);
    }

    /** @test */
    public function it_fails_to_create_duplicate_review_for_same_order_and_item()
    {
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->expectException(DuplicateReviewException::class);
        $this->service->createReview([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
        ]);
    }

    /** @test */
    public function it_validates_rating_must_be_between_1_and_5()
    {
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->createReview([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 6,
        ]);
    }

    /** @test */
    public function it_updates_pending_review_successfully()
    {
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
            'rating' => 3,
            'review_text' => 'Initial review',
        ]);

        $updated = $this->service->updateReview($review->id, $guest->id, [
            'rating' => 5,
            'review_text' => 'Updated review text',
        ]);

        $this->assertEquals(5, $updated->rating);
        $this->assertEquals('Updated review text', $updated->review_text);
    }

    /** @test */
    public function it_prevents_updating_approved_review()
    {
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest->id,
        ]);

        $this->expectException(ReviewNotModifiableException::class);
        $this->service->updateReview($review->id, $guest->id, [
            'rating' => 5,
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_user_from_updating_review()
    {
        $guest = Guest::factory()->create();
        $otherGuest = Guest::factory()->create();
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
        ]);

        $this->expectException(UnauthorizedReviewAccessException::class);
        $this->service->updateReview($review->id, $otherGuest->id, [
            'rating' => 5,
        ]);
    }

    /** @test */
    public function it_deletes_pending_review_successfully()
    {
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $guest->id,
        ]);

        $result = $this->service->deleteReview($review->id, $guest->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('menu_item_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function it_prevents_deleting_approved_review()
    {
        $guest = Guest::factory()->create();
        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $guest->id,
        ]);

        $this->expectException(ReviewNotModifiableException::class);
        $this->service->deleteReview($review->id, $guest->id);
    }
}
