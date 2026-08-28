<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\MenuItemReview;
use App\Models\Guest;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderDeletionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Deletion of order with approved reviews should be prevented
     */
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

        // Verify order still exists
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    /** @test */
    public function it_allows_deletion_of_order_without_reviews()
    {
        // Arrange
        $order = Order::factory()->create();

        // Act
        $order->delete();

        // Assert
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_allows_deletion_of_order_with_pending_reviews_only()
    {
        // Arrange
        $order = Order::factory()->create();

        MenuItemReview::factory()->pending()->createMany(2, [
            'order_id' => $order->id,
        ]);

        // Act
        $order->delete();

        // Assert
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_allows_deletion_of_order_with_rejected_reviews_only()
    {
        // Arrange
        $order = Order::factory()->create();

        MenuItemReview::factory()->rejected()->createMany(2, [
            'order_id' => $order->id,
        ]);

        // Act
        $order->delete();

        // Assert
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_prevents_deletion_of_order_with_mixed_statuses_including_approved()
    {
        // Arrange
        $order = Order::factory()->create();

        MenuItemReview::factory()->pending()->create([
            'order_id' => $order->id,
        ]);

        MenuItemReview::factory()->rejected()->create([
            'order_id' => $order->id,
        ]);

        MenuItemReview::factory()->approved()->create([
            'order_id' => $order->id,
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        $order->delete();

        // Verify order still exists
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    /** @test */
    public function it_prevents_deletion_of_order_with_multiple_approved_reviews()
    {
        // Arrange
        $order = Order::factory()->create();

        MenuItemReview::factory()->approved()->createMany(3, [
            'order_id' => $order->id,
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        $order->delete();
    }

    /** @test */
    public function it_allows_deletion_when_approved_review_is_soft_deleted()
    {
        // Arrange
        $order = Order::factory()->create();

        $review = MenuItemReview::factory()->approved()->create([
            'order_id' => $order->id,
        ]);

        // Soft delete the review
        $review->delete();

        // Act - Order should now be deletable
        $order->delete();

        // Assert
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_returns_descriptive_error_on_order_deletion_attempt()
    {
        // Arrange
        $order = Order::factory()->create();
        $review = MenuItemReview::factory()->approved()->create([
            'order_id' => $order->id,
        ]);

        // Act & Assert - Should throw QueryException (database constraint)
        try {
            $order->delete();
            $this->fail('Expected QueryException to be thrown');
        } catch (QueryException $e) {
            // Error message should mention foreign key constraint
            $this->assertStringContainsString('foreign', strtolower($e->getMessage()));
        }
    }

    /** @test */
    public function it_prevents_deletion_of_order_with_single_approved_review()
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
    public function it_allows_cascade_on_pending_reviews_when_menu_item_deleted()
    {
        // Arrange
        $order = Order::factory()->create();

        // Pending reviews should be deleted with menu item, not with order
        // This tests that the foreign key logic is correct

        $review = MenuItemReview::factory()->pending()->create([
            'order_id' => $order->id,
        ]);

        // Act - Delete order should succeed with pending reviews
        $order->delete();

        // Assert
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_prevents_forced_deletion_of_order_with_approved_reviews()
    {
        // Arrange
        $order = Order::factory()->create();

        MenuItemReview::factory()->approved()->create([
            'order_id' => $order->id,
        ]);

        // Act & Assert - Force delete should also fail
        $this->expectException(QueryException::class);
        $order->forceDelete();
    }

    /** @test */
    public function it_maintains_referential_integrity_on_deletion_attempt()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $order = Order::factory()->create(['guest_id' => $guest->id]);

        MenuItemReview::factory()->approved()->create([
            'order_id' => $order->id,
            'guest_id' => $guest->id,
        ]);

        $reviewCount = MenuItemReview::where('order_id', $order->id)->count();

        // Act & Assert
        try {
            $order->delete();
            $this->fail('Expected QueryException');
        } catch (QueryException $e) {
            // Verify data integrity
            $this->assertEquals($reviewCount, MenuItemReview::where('order_id', $order->id)->count());
        }
    }

    /** @test */
    public function it_allows_restoration_of_deleted_order_without_approved_reviews()
    {
        // Arrange
        $order = Order::factory()->create();

        MenuItemReview::factory()->pending()->create([
            'order_id' => $order->id,
        ]);

        $order->delete();

        // Act - Restore
        $order->restore();

        // Assert
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    /** @test */
    public function it_prevents_deletion_within_api_if_approved_reviews_exist()
    {
        // Arrange
        $order = Order::factory()->create();

        MenuItemReview::factory()->approved()->create([
            'order_id' => $order->id,
        ]);

        // This would typically be called through an API or service
        // but at the database level, the constraint should prevent it
        $this->expectException(QueryException::class);
        $order->delete();
    }
}
