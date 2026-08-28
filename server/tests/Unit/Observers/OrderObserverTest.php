<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Order;
use App\Models\MenuItemReview;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class OrderObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that order can be deleted when it has no reviews
     */
    public function test_it_allows_deletion_when_order_has_no_reviews()
    {
        // Create an order without reviews
        $order = Order::factory()->create([
            'status' => Order::STATUS_SERVED,
        ]);

        // Attempt to delete - should succeed
        $result = $order->delete();

        $this->assertTrue($result);
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /**
     * Test that order can be deleted when it has only pending reviews
     */
    public function test_it_allows_deletion_when_order_has_only_pending_reviews()
    {
        // Create an order with a pending review
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
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Attempt to delete - should succeed because review is pending
        $result = $order->delete();

        $this->assertTrue($result);
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /**
     * Test that order can be deleted when it has only rejected reviews
     */
    public function test_it_allows_deletion_when_order_has_only_rejected_reviews()
    {
        // Create an order with a rejected review
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
            'status' => MenuItemReview::STATUS_REJECTED,
        ]);

        // Attempt to delete - should succeed because review is rejected
        $result = $order->delete();

        $this->assertTrue($result);
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /**
     * Test that order deletion is prevented when it has approved reviews
     * This validates Requirement 7.3: "WHEN an Order is deleted, THE Review_System 
     * SHALL prevent deletion if approved reviews exist and return an error"
     */
    public function test_it_prevents_deletion_when_order_has_approved_reviews()
    {
        // Create an order with an approved review
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
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Expect exception when trying to delete
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete order with approved reviews');

        $order->delete();
    }

    /**
     * Test that order deletion is prevented when it has multiple approved reviews
     */
    public function test_it_prevents_deletion_when_order_has_multiple_approved_reviews()
    {
        // Create an order with multiple approved reviews
        $guest = Guest::factory()->create();
        $menuItem1 = MenuItem::factory()->create();
        $menuItem2 = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem2->id,
        ]);

        // Create two approved reviews
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem2->id,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Expect exception when trying to delete
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete order with approved reviews');

        $order->delete();
    }

    /**
     * Test that order deletion is prevented when it has at least one approved review
     * mixed with other status reviews
     */
    public function test_it_prevents_deletion_when_order_has_mixed_reviews_including_approved()
    {
        // Create an order with mixed review statuses
        $guest = Guest::factory()->create();
        $menuItem1 = MenuItem::factory()->create();
        $menuItem2 = MenuItem::factory()->create();
        $menuItem3 = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem2->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem3->id,
        ]);

        // Create reviews with different statuses
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem2->id,
            'status' => MenuItemReview::STATUS_APPROVED, // One approved review
        ]);

        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem3->id,
            'status' => MenuItemReview::STATUS_REJECTED,
        ]);

        // Expect exception when trying to delete
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete order with approved reviews');

        $order->delete();
    }

    /**
     * Test that the order is not deleted after exception is thrown
     */
    public function test_it_does_not_delete_order_when_approved_reviews_exist()
    {
        // Create an order with an approved review
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
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Attempt to delete and catch the exception
        try {
            $order->delete();
        } catch (Exception $e) {
            // Expected exception
        }

        // Verify order still exists
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'deleted_at' => null,
        ]);
    }
}
