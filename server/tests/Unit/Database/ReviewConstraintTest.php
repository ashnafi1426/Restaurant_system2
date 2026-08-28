<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\MenuItemReview;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewConstraintTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_enforces_unique_constraint_on_guest_order_menu_item_combination()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);
    }

    /** @test */
    public function it_allows_same_guest_to_review_same_item_from_different_orders()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order1 = Order::factory()->create(['guest_id' => $guest->id]);
        $order2 = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order1->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Should not throw exception
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order2->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Assert
        $this->assertDatabaseHas('menu_item_reviews', [
            'guest_id' => $guest->id,
            'order_id' => $order1->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->assertDatabaseHas('menu_item_reviews', [
            'guest_id' => $guest->id,
            'order_id' => $order2->id,
            'menu_item_id' => $menuItem->id,
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_guest_id()
    {
        // Arrange
        $order = Order::factory()->create();
        $menuItem = MenuItem::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => 'nonexistent-guest-id',
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_order_id()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => 'nonexistent-order-id',
            'menu_item_id' => $menuItem->id,
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_menu_item_id()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);

        // Act & Assert
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => 'nonexistent-menu-item-id',
        ]);
    }

    /** @test */
    public function it_cascade_deletes_reviews_when_menu_item_is_deleted()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $review = MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
        ]);

        // Act
        $menuItem->delete();

        // Assert
        $this->assertSoftDeleted('menu_items', ['id' => $menuItem->id]);
        $this->assertDatabaseMissing('menu_item_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function it_prevents_deletion_of_order_with_approved_reviews()
    {
        // Arrange
        $order = Order::factory()->create();
        MenuItemReview::factory()->approved()->create([
            'order_id' => $order->id,
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        $order->delete();
    }

    /** @test */
    public function it_allows_deletion_of_order_without_approved_reviews()
    {
        // Arrange
        $order = Order::factory()->create();
        MenuItemReview::factory()->pending()->create([
            'order_id' => $order->id,
        ]);
        MenuItemReview::factory()->rejected()->create([
            'order_id' => $order->id,
        ]);

        // Act
        $order->delete();

        // Assert
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_enforces_rating_check_constraint()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act & Assert - Rating must be 1-5
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 6,
        ]);
    }

    /** @test */
    public function it_enforces_rating_lower_bound()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 0,
        ]);
    }

    /** @test */
    public function it_accepts_valid_rating_values()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act & Assert
        for ($rating = 1; $rating <= 5; $rating++) {
            $review = MenuItemReview::factory()->create([
                'guest_id' => $guest->id,
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'rating' => $rating,
            ]);

            $this->assertDatabaseHas('menu_item_reviews', [
                'id' => $review->id,
                'rating' => $rating,
            ]);
        }
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_approved_by()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act & Assert - Invalid approved_by user should fail
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'status' => MenuItemReview::STATUS_APPROVED,
            'approved_by' => 'nonexistent-user-id',
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraint_on_rejected_by()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act & Assert
        $this->expectException(QueryException::class);
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'status' => MenuItemReview::STATUS_REJECTED,
            'rejected_by' => 'nonexistent-user-id',
        ]);
    }

    /** @test */
    public function it_allows_null_approved_by_for_pending_reviews()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'status' => MenuItemReview::STATUS_PENDING,
            'approved_by' => null,
        ]);

        // Assert
        $this->assertNull($review->approved_by);
    }

    /** @test */
    public function it_allows_null_rejected_by_for_pending_reviews()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem = MenuItem::factory()->create();

        // Act
        $review = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'status' => MenuItemReview::STATUS_PENDING,
            'rejected_by' => null,
        ]);

        // Assert
        $this->assertNull($review->rejected_by);
    }

    /** @test */
    public function it_enforces_soft_delete_on_reviews()
    {
        // Arrange
        $review = MenuItemReview::factory()->create();

        // Act
        $review->delete();

        // Assert
        $this->assertSoftDeleted('menu_item_reviews', ['id' => $review->id]);
        $this->assertNotNull($review->deleted_at);
    }

    /** @test */
    public function it_allows_multiple_reviews_for_different_menu_items_in_same_order()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $menuItem1 = MenuItem::factory()->create();
        $menuItem2 = MenuItem::factory()->create();

        // Act
        $review1 = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
        ]);

        $review2 = MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem2->id,
        ]);

        // Assert
        $this->assertNotEquals($review1->id, $review2->id);
        $this->assertDatabaseHas('menu_item_reviews', ['id' => $review1->id]);
        $this->assertDatabaseHas('menu_item_reviews', ['id' => $review2->id]);
    }
}
