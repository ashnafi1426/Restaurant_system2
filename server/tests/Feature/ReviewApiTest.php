<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Guest;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItemReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    protected Guest $guest;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guest = Guest::factory()->create();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_creates_a_review_with_valid_data()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $data = [
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'review_text' => 'Excellent food and quick delivery!',
        ];

        // Act
        $response = $this->postJson('/api/reviews', $data);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'guest_id',
                'order_id',
                'menu_item_id',
                'rating',
                'review_text',
                'status',
            ],
        ]);
        $this->assertDatabaseHas('menu_item_reviews', [
            'guest_id' => $this->guest->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function it_rejects_review_without_purchase_verification()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $otherItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $otherItem->id,
        ]);

        $data = [
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
        ];

        // Act
        $response = $this->postJson('/api/reviews', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'You must order this item before reviewing it',
        ]);
    }

    /** @test */
    public function it_rejects_review_from_incomplete_order()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'status' => Order::STATUS_PENDING,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $data = [
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
        ];

        // Act
        $response = $this->postJson('/api/reviews', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'You can only review items from completed orders',
        ]);
    }

    /** @test */
    public function it_rejects_duplicate_review()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        MenuItemReview::factory()->create([
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $data = [
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ];

        // Act
        $response = $this->postJson('/api/reviews', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'You have already reviewed this item for this order',
        ]);
    }

    /** @test */
    public function it_validates_rating_between_1_and_5()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        // Test invalid rating
        $data = [
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 6,
        ];

        // Act
        $response = $this->postJson('/api/reviews', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rating');
    }

    /** @test */
    public function it_returns_review_details()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create();

        // Act
        $response = $this->getJson("/api/reviews/{$review->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $review->id,
            'rating' => $review->rating,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
    }

    /** @test */
    public function it_updates_pending_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $this->guest->id,
            'rating' => 3,
        ]);

        $data = [
            'rating' => 5,
            'review_text' => 'Changed my mind, it was great!',
        ];

        // Act
        $response = $this->putJson("/api/reviews/{$review->id}", $data);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $review->id,
            'rating' => 5,
            'review_text' => 'Changed my mind, it was great!',
        ]);
    }

    /** @test */
    public function it_prevents_updating_approved_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->approved()->create([
            'guest_id' => $this->guest->id,
        ]);

        $data = [
            'rating' => 5,
        ];

        // Act
        $response = $this->putJson("/api/reviews/{$review->id}", $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot modify a moderated review',
        ]);
    }

    /** @test */
    public function it_deletes_pending_review()
    {
        // Arrange
        $review = MenuItemReview::factory()->pending()->create([
            'guest_id' => $this->guest->id,
        ]);

        // Act
        $response = $this->deleteJson("/api/reviews/{$review->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('menu_item_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function it_returns_eligible_items_for_review()
    {
        // Arrange
        $menuItem1 = MenuItem::factory()->create();
        $menuItem2 = MenuItem::factory()->create();
        $menuItem3 = MenuItem::factory()->create();

        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
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

        // Create a review for menuItem1
        MenuItemReview::factory()->create([
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem1->id,
        ]);

        // Act
        $response = $this->getJson("/api/guests/{$this->guest->id}/eligible-items");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($menuItem2->id, $response->json('data')[0]['id']);
    }

    /** @test */
    public function it_validates_review_text_max_length()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $data = [
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'review_text' => str_repeat('a', 1001), // Exceeds 1000 char limit
        ];

        // Act
        $response = $this->postJson('/api/reviews', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('review_text');
    }

    /** @test */
    public function it_requires_authentication_to_create_review()
    {
        // Act - Without authentication
        Sanctum::actingAs(null);
        
        $response = $this->postJson('/api/reviews', [
            'guest_id' => $this->guest->id,
            'order_id' => 'some-id',
            'menu_item_id' => 'some-id',
            'rating' => 5,
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_strips_html_from_review_text()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $order = Order::factory()->create([
            'guest_id' => $this->guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $data = [
            'guest_id' => $this->guest->id,
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'review_text' => '<script>alert("xss")</script>Great food!',
        ];

        // Act
        $response = $this->postJson('/api/reviews', $data);

        // Assert
        $response->assertStatus(201);
        $review = MenuItemReview::find($response->json('data.id'));
        $this->assertStringNotContainsString('<script>', $review->review_text);
        $this->assertStringNotContainsString('alert', $review->review_text);
    }
}
