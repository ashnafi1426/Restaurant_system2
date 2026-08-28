<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PurchaseVerificationService;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\MenuItemReview;
use App\Exceptions\PurchaseNotVerifiedException;
use App\Exceptions\DuplicateReviewException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PurchaseVerificationService();
    }

    /** @test */
    public function it_passes_verification_when_guest_has_completed_order_with_menu_item()
    {
        // Arrange
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

        // Act & Assert - should not throw any exception
        $this->service->verifyPurchase($guest->id, $menuItem->id, $order->id);
        $this->assertTrue(true); // If we get here, verification passed
    }

    /** @test */
    public function it_throws_exception_when_order_does_not_exist()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $nonExistentOrderId = 'non-existent-order-id';

        // Act & Assert
        $this->expectException(PurchaseNotVerifiedException::class);
        $this->expectExceptionMessage('You must order this item before reviewing it');
        
        $this->service->verifyPurchase($guest->id, $menuItem->id, $nonExistentOrderId);
    }

    /** @test */
    public function it_throws_exception_when_order_belongs_to_different_guest()
    {
        // Arrange
        $guest1 = Guest::factory()->create();
        $guest2 = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest1->id,
            'status' => Order::STATUS_SERVED,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Act & Assert
        $this->expectException(PurchaseNotVerifiedException::class);
        $this->expectExceptionMessage('You must order this item before reviewing it');
        
        $this->service->verifyPurchase($guest2->id, $menuItem->id, $order->id);
    }

    /** @test */
    public function it_throws_exception_when_order_is_not_completed()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_PENDING, // Not completed
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Act & Assert
        $this->expectException(PurchaseNotVerifiedException::class);
        $this->expectExceptionMessage('You can only review items from completed orders');
        
        $this->service->verifyPurchase($guest->id, $menuItem->id, $order->id);
    }

    /** @test */
    public function it_throws_exception_when_order_does_not_contain_menu_item()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $menuItem1 = MenuItem::factory()->create();
        $menuItem2 = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        
        // Order contains menuItem1 but not menuItem2
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
        ]);

        // Act & Assert
        $this->expectException(PurchaseNotVerifiedException::class);
        $this->expectExceptionMessage('You must order this item before reviewing it');
        
        $this->service->verifyPurchase($guest->id, $menuItem2->id, $order->id);
    }

    /** @test */
    public function it_passes_duplicate_check_when_no_review_exists()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);

        // Act & Assert - should not throw any exception
        $this->service->checkDuplicateReview($guest->id, $menuItem->id, $order->id);
        $this->assertTrue(true); // If we get here, check passed
    }

    /** @test */
    public function it_throws_exception_when_duplicate_review_exists()
    {
        // Arrange
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

        // Create existing review
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Act & Assert
        $this->expectException(DuplicateReviewException::class);
        $this->expectExceptionMessage('You have already reviewed this item for this order');
        
        $this->service->checkDuplicateReview($guest->id, $menuItem->id, $order->id);
    }

    /** @test */
    public function it_allows_same_guest_to_review_same_item_from_different_orders()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        
        $order1 = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        
        $order2 = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        
        // Create review for order1
        MenuItemReview::factory()->create([
            'guest_id' => $guest->id,
            'order_id' => $order1->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Act & Assert - should not throw exception for order2
        $this->service->checkDuplicateReview($guest->id, $menuItem->id, $order2->id);
        $this->assertTrue(true); // If we get here, check passed
    }

    /** @test */
    public function it_allows_different_guests_to_review_same_item_from_same_order()
    {
        // Arrange
        $guest1 = Guest::factory()->create();
        $guest2 = Guest::factory()->create();
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $guest1->id,
            'status' => Order::STATUS_SERVED,
        ]);
        
        // Create review for guest1
        MenuItemReview::factory()->create([
            'guest_id' => $guest1->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Act & Assert - should not throw exception for guest2
        $this->service->checkDuplicateReview($guest2->id, $menuItem->id, $order->id);
        $this->assertTrue(true); // If we get here, check passed
    }
}
