<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MenuItem;
use App\Models\MenuItemReview;
use App\Models\ReviewResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicReviewApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_only_approved_reviews()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->createMany(3)->each(function ($review) use ($menuItem) {
            $review->update(['menu_item_id' => $menuItem->id]);
        });
        
        MenuItemReview::factory()->pending()->createMany(2)->each(function ($review) use ($menuItem) {
            $review->update(['menu_item_id' => $menuItem->id]);
        });

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
        $this->assertCount(3, $response->json('data'));
        
        foreach ($response->json('data') as $review) {
            $this->assertEquals(MenuItemReview::STATUS_APPROVED, $review['status']);
        }
    }

    /** @test */
    public function it_paginates_with_10_reviews_per_page()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->createMany(25)->each(function ($review) use ($menuItem) {
            $review->update(['menu_item_id' => $menuItem->id]);
        });

        // Act
        $page1 = $this->getJson("/api/menu-items/{$menuItem->id}/reviews?page=1");
        $page2 = $this->getJson("/api/menu-items/{$menuItem->id}/reviews?page=2");
        $page3 = $this->getJson("/api/menu-items/{$menuItem->id}/reviews?page=3");

        // Assert
        $page1->assertStatus(200);
        $this->assertCount(10, $page1->json('data'));
        $this->assertCount(10, $page2->json('data'));
        $this->assertCount(5, $page3->json('data'));
        $this->assertEquals(25, $page1->json('total'));
    }

    /** @test */
    public function it_sorts_by_newest_first_by_default()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        $review1 = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $review2 = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'created_at' => now(),
        ]);
        
        $review3 = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'created_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($review2->id, $data[0]['id']);
        $this->assertEquals($review3->id, $data[1]['id']);
        $this->assertEquals($review1->id, $data[2]['id']);
    }

    /** @test */
    public function it_supports_sorting_by_helpfulness()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        $helpful = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'helpful_count' => 10,
            'not_helpful_count' => 2,
        ]);
        
        $neutral = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'helpful_count' => 5,
            'not_helpful_count' => 5,
        ]);
        
        $unhelpful = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'helpful_count' => 2,
            'not_helpful_count' => 10,
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews?sort=helpful");

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($helpful->id, $data[0]['id']);
    }

    /** @test */
    public function it_anonymizes_guest_names()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $review = $response->json('data.0');
        
        // Check that guest name is anonymized (first name + last initial)
        $this->assertNotNull($review['guest_name']);
        $this->assertStringNotContainsString('@', $review['guest_name']);
    }

    /** @test */
    public function it_returns_null_rating_when_no_reviews_exist()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
        $this->assertNull($response->json('average_rating'));
    }

    /** @test */
    public function it_includes_helpful_and_not_helpful_counts()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'helpful_count' => 15,
            'not_helpful_count' => 5,
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $review = $response->json('data.0');
        $this->assertEquals(15, $review['helpful_count']);
        $this->assertEquals(5, $review['not_helpful_count']);
    }

    /** @test */
    public function it_includes_management_response_if_exists()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        $review = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
        ]);
        
        $responder = User::factory()->create(['role' => 'manager']);
        ReviewResponse::factory()->create([
            'review_id' => $review->id,
            'responder_id' => $responder->id,
            'response_text' => 'Thank you for your feedback!',
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $review = $response->json('data.0');
        $this->assertNotNull($review['response']);
        $this->assertEquals('Thank you for your feedback!', $review['response']['text']);
        $this->assertNotNull($review['response']['responder_role']);
    }

    /** @test */
    public function it_does_not_require_authentication()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
        ]);

        // Act - No authentication
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_rating_and_review_count()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ]);
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(4.0, $response->json('average_rating'));
        $this->assertEquals(2, $response->json('review_count'));
    }

    /** @test */
    public function it_returns_empty_list_for_nonexistent_menu_item()
    {
        // Act
        $response = $this->getJson('/api/menu-items/nonexistent-id/reviews');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
    }

    /** @test */
    public function it_excludes_rejected_reviews()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
        ]);
        
        MenuItemReview::factory()->rejected()->create([
            'menu_item_id' => $menuItem->id,
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
    }

    /** @test */
    public function it_includes_creation_date()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        $review = MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $reviewData = $response->json('data.0');
        $this->assertNotNull($reviewData['created_at']);
    }

    /** @test */
    public function it_calculates_helpfulness_ratio()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'helpful_count' => 8,
            'not_helpful_count' => 2,
        ]);

        // Act
        $response = $this->getJson("/api/menu-items/{$menuItem->id}/reviews");

        // Assert
        $response->assertStatus(200);
        $review = $response->json('data.0');
        $this->assertEquals(0.8, $review['helpfulness_ratio']);
    }
}
