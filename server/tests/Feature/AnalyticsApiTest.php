<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\MenuItemReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;
    protected User $manager;
    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->guest = User::factory()->create(['role' => 'guest']);
        Sanctum::actingAs($this->manager);
    }

    /** @test */
    public function it_returns_menu_item_statistics()
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
        $response = $this->getJson("/api/admin/analytics/menu-items/{$menuItem->id}/stats");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_reviews',
            'average_rating',
            'distribution',
            'percentages',
        ]);
        $this->assertEquals(2, $response->json('total_reviews'));
        $this->assertEquals(4.0, $response->json('average_rating'));
    }

    /** @test */
    public function it_returns_top_rated_items()
    {
        // Arrange
        for ($i = 0; $i < 3; $i++) {
            $item = MenuItem::factory()->create();
            for ($j = 0; $j < 6; $j++) {
                MenuItemReview::factory()->approved()->create([
                    'menu_item_id' => $item->id,
                    'rating' => 5 - $i,
                ]);
            }
        }

        // Act
        $response = $this->getJson('/api/admin/analytics/top-rated?min_reviews=5');

        // Assert
        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /** @test */
    public function it_returns_lowest_rated_items()
    {
        // Arrange
        for ($i = 0; $i < 3; $i++) {
            $item = MenuItem::factory()->create();
            for ($j = 0; $j < 6; $j++) {
                MenuItemReview::factory()->approved()->create([
                    'menu_item_id' => $item->id,
                    'rating' => $i + 1,
                ]);
            }
        }

        // Act
        $response = $this->getJson('/api/admin/analytics/lowest-rated?min_reviews=5');

        // Assert
        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /** @test */
    public function it_returns_pending_review_count()
    {
        // Arrange
        MenuItemReview::factory()->pending()->createMany(5);
        MenuItemReview::factory()->approved()->createMany(3);
        MenuItemReview::factory()->rejected()->createMany(2);

        // Act
        $response = $this->getJson('/api/admin/analytics/pending-count');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('pending_count'));
    }

    /** @test */
    public function it_returns_review_trends()
    {
        // Arrange
        MenuItemReview::factory()->createMany(10);

        // Act
        $response = $this->getJson('/api/admin/analytics/review-trends?period=daily');

        // Assert
        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    /** @test */
    public function guest_cannot_access_analytics()
    {
        // Arrange
        Sanctum::actingAs($this->guest);

        // Act
        $response = $this->getJson('/api/admin/analytics/pending-count');

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication_for_analytics()
    {
        // Act
        Sanctum::actingAs(null);
        $response = $this->getJson('/api/admin/analytics/pending-count');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_rating_distribution()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ]);
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ]);
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
        ]);

        // Act
        $response = $this->getJson("/api/admin/analytics/menu-items/{$menuItem->id}/stats");

        // Assert
        $response->assertStatus(200);
        $distribution = $response->json('distribution');
        $this->assertEquals(0, $distribution[1]);
        $this->assertEquals(0, $distribution[2]);
        $this->assertEquals(1, $distribution[3]);
        $this->assertEquals(0, $distribution[4]);
        $this->assertEquals(2, $distribution[5]);
    }

    /** @test */
    public function it_filters_top_rated_by_minimum_reviews()
    {
        // Arrange
        $topItem = MenuItem::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $topItem->id,
                'rating' => 5,
            ]);
        }

        $lowItem = MenuItem::factory()->create();
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $lowItem->id,
            'rating' => 5,
        ]);

        // Act
        $response = $this->getJson('/api/admin/analytics/top-rated?min_reviews=5');

        // Assert
        $response->assertStatus(200);
        $items = $response->json('data');
        $this->assertTrue(
            collect($items)->pluck('id')->contains($topItem->id),
            'Top rated item should be included'
        );
    }

    /** @test */
    public function it_respects_limit_parameter()
    {
        // Arrange
        for ($i = 0; $i < 20; $i++) {
            $item = MenuItem::factory()->create();
            for ($j = 0; $j < 5; $j++) {
                MenuItemReview::factory()->approved()->create([
                    'menu_item_id' => $item->id,
                    'rating' => 5,
                ]);
            }
        }

        // Act
        $response = $this->getJson('/api/admin/analytics/top-rated?min_reviews=5&limit=5');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function admin_can_access_analytics()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Act
        $response = $this->getJson('/api/admin/analytics/pending-count');

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_percentage_distribution()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // 10 reviews: 2x5-star, 3x4-star, 5x1-star
        for ($i = 0; $i < 2; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $menuItem->id,
                'rating' => 5,
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $menuItem->id,
                'rating' => 4,
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $menuItem->id,
                'rating' => 1,
            ]);
        }

        // Act
        $response = $this->getJson("/api/admin/analytics/menu-items/{$menuItem->id}/stats");

        // Assert
        $response->assertStatus(200);
        $percentages = $response->json('percentages');
        $this->assertEquals(20, $percentages[5]); // 2/10 * 100
        $this->assertEquals(30, $percentages[4]); // 3/10 * 100
        $this->assertEquals(50, $percentages[1]); // 5/10 * 100
    }

    /** @test */
    public function it_returns_empty_stats_for_nonexistent_menu_item()
    {
        // Act
        $response = $this->getJson('/api/admin/analytics/menu-items/nonexistent-id/stats');

        // Assert
        $response->assertStatus(200);
        $this->assertNull($response->json('average_rating'));
    }

    /** @test */
    public function it_supports_different_trend_periods()
    {
        // Arrange
        MenuItemReview::factory()->createMany(5);

        // Act
        $daily = $this->getJson('/api/admin/analytics/review-trends?period=daily');
        $weekly = $this->getJson('/api/admin/analytics/review-trends?period=weekly');
        $monthly = $this->getJson('/api/admin/analytics/review-trends?period=monthly');

        // Assert
        $daily->assertStatus(200);
        $weekly->assertStatus(200);
        $monthly->assertStatus(200);
    }
}
