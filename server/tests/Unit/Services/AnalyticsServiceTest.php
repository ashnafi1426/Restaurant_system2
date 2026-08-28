<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\MenuItemReview;
use App\Models\MenuItem;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService();
    }

    /** @test */
    public function it_calculates_menu_item_statistics_correctly()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ]);
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
        ]);
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
        ]);
        MenuItemReview::factory()->pending()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ]);

        // Act
        $stats = $this->service->getMenuItemStatistics($menuItem->id);

        // Assert
        $this->assertEquals(3, $stats['total_reviews']);
        $this->assertEquals(4.0, $stats['average_rating']);
        $this->assertIsArray($stats['distribution']);
        $this->assertIsArray($stats['percentages']);
    }

    /** @test */
    public function it_returns_correct_rating_distribution()
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
            'rating' => 4,
        ]);
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 1,
        ]);

        // Act
        $stats = $this->service->getMenuItemStatistics($menuItem->id);

        // Assert
        $this->assertEquals(1, $stats['distribution'][1]);
        $this->assertEquals(0, $stats['distribution'][2]);
        $this->assertEquals(0, $stats['distribution'][3]);
        $this->assertEquals(1, $stats['distribution'][4]);
        $this->assertEquals(2, $stats['distribution'][5]);
    }

    /** @test */
    public function it_returns_top_rated_items_with_minimum_reviews()
    {
        // Arrange
        $item1 = MenuItem::factory()->create();
        $item2 = MenuItem::factory()->create();
        $item3 = MenuItem::factory()->create();

        // Item1: 6 reviews, avg 5.0
        for ($i = 0; $i < 6; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $item1->id,
                'rating' => 5,
            ]);
        }

        // Item2: 3 reviews, avg 4.5 (below minimum of 5)
        for ($i = 0; $i < 3; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $item2->id,
                'rating' => 4,
            ]);
        }

        // Item3: 5 reviews, avg 4.0
        for ($i = 0; $i < 5; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $item3->id,
                'rating' => 4,
            ]);
        }

        // Act
        $topRated = $this->service->getTopRatedItems(5, 10);

        // Assert
        $this->assertCount(2, $topRated);
        $this->assertEquals($item1->id, $topRated[0]->id);
        $this->assertEquals($item3->id, $topRated[1]->id);
    }

    /** @test */
    public function it_returns_lowest_rated_items_with_minimum_reviews()
    {
        // Arrange
        $item1 = MenuItem::factory()->create();
        $item2 = MenuItem::factory()->create();
        $item3 = MenuItem::factory()->create();

        // Item1: 5 reviews, avg 1.0
        for ($i = 0; $i < 5; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $item1->id,
                'rating' => 1,
            ]);
        }

        // Item2: 3 reviews, avg 2.5 (below minimum)
        for ($i = 0; $i < 3; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $item2->id,
                'rating' => 2,
            ]);
        }

        // Item3: 5 reviews, avg 3.0
        for ($i = 0; $i < 5; $i++) {
            MenuItemReview::factory()->approved()->create([
                'menu_item_id' => $item3->id,
                'rating' => 3,
            ]);
        }

        // Act
        $lowestRated = $this->service->getLowestRatedItems(5, 10);

        // Assert
        $this->assertCount(2, $lowestRated);
        $this->assertEquals($item1->id, $lowestRated[0]->id);
        $this->assertEquals($item3->id, $lowestRated[1]->id);
    }

    /** @test */
    public function it_returns_pending_review_count()
    {
        // Arrange
        MenuItemReview::factory()->pending()->createMany(5);
        MenuItemReview::factory()->approved()->createMany(3);
        MenuItemReview::factory()->rejected()->createMany(2);

        // Act
        $pendingCount = $this->service->getPendingReviewCount();

        // Assert
        $this->assertEquals(5, $pendingCount);
    }

    /** @test */
    public function it_returns_zero_pending_count_when_no_pending_reviews()
    {
        // Arrange
        MenuItemReview::factory()->approved()->createMany(3);
        MenuItemReview::factory()->rejected()->createMany(2);

        // Act
        $pendingCount = $this->service->getPendingReviewCount();

        // Assert
        $this->assertEquals(0, $pendingCount);
    }

    /** @test */
    public function it_calculates_daily_review_trends()
    {
        // Arrange
        $today = now();
        
        MenuItemReview::factory()->create([
            'created_at' => $today,
        ]);
        MenuItemReview::factory()->create([
            'created_at' => $today,
        ]);
        MenuItemReview::factory()->create([
            'created_at' => $today->subDay(),
        ]);

        // Act
        $trends = $this->service->getReviewTrends('daily');

        // Assert
        $this->assertIsArray($trends);
        $this->assertNotEmpty($trends);
    }

    /** @test */
    public function it_excludes_pending_reviews_from_statistics()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        MenuItemReview::factory()->approved()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
        ]);
        MenuItemReview::factory()->pending()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 1,
        ]);

        // Act
        $stats = $this->service->getMenuItemStatistics($menuItem->id);

        // Assert
        $this->assertEquals(1, $stats['total_reviews']);
        $this->assertEquals(5.0, $stats['average_rating']);
    }

    /** @test */
    public function it_returns_empty_list_when_no_items_meet_minimum_threshold()
    {
        // Arrange
        MenuItem::factory()->create();
        MenuItem::factory()->create();
        // No reviews created

        // Act
        $topRated = $this->service->getTopRatedItems(5, 10);
        $lowestRated = $this->service->getLowestRatedItems(5, 10);

        // Assert
        $this->assertCount(0, $topRated);
        $this->assertCount(0, $lowestRated);
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
        $topRated = $this->service->getTopRatedItems(5, 5);

        // Assert
        $this->assertCount(5, $topRated);
    }

    /** @test */
    public function it_calculates_percentage_distribution_correctly()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // 10 reviews total: 1x5-star, 2x4-star, 3x3-star, 2x2-star, 2x1-star
        MenuItemReview::factory()->approved()->create(['menu_item_id' => $menuItem->id, 'rating' => 5]);
        for ($i = 0; $i < 2; $i++) {
            MenuItemReview::factory()->approved()->create(['menu_item_id' => $menuItem->id, 'rating' => 4]);
        }
        for ($i = 0; $i < 3; $i++) {
            MenuItemReview::factory()->approved()->create(['menu_item_id' => $menuItem->id, 'rating' => 3]);
        }
        for ($i = 0; $i < 2; $i++) {
            MenuItemReview::factory()->approved()->create(['menu_item_id' => $menuItem->id, 'rating' => 2]);
        }
        for ($i = 0; $i < 2; $i++) {
            MenuItemReview::factory()->approved()->create(['menu_item_id' => $menuItem->id, 'rating' => 1]);
        }

        // Act
        $stats = $this->service->getMenuItemStatistics($menuItem->id);

        // Assert
        $this->assertEquals(10, $stats['total_reviews']);
        $this->assertEquals(10, $stats['percentages'][5]);  // 1/10 * 100
        $this->assertEquals(20, $stats['percentages'][4]);  // 2/10 * 100
        $this->assertEquals(30, $stats['percentages'][3]);  // 3/10 * 100
        $this->assertEquals(20, $stats['percentages'][2]);  // 2/10 * 100
        $this->assertEquals(20, $stats['percentages'][1]);  // 2/10 * 100
    }
}
