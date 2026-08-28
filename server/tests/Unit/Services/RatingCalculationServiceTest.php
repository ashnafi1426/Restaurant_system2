<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RatingCalculationService;
use App\Models\MenuItem;
use App\Models\MenuItemReview;
use App\Models\Guest;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class RatingCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RatingCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RatingCalculationService();
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_it_calculates_average_rating_correctly()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // Create 3 approved reviews with ratings 5, 4, 3
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals(4.0, $stats['average_rating']); // (5+4+3)/3 = 4.0
        $this->assertEquals(3, $stats['review_count']);
    }

    public function test_it_excludes_pending_reviews_from_calculation()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // Create 2 approved reviews
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        
        // Create 1 pending review (should not be included)
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 1,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals(4.5, $stats['average_rating']); // (5+4)/2 = 4.5, pending review excluded
        $this->assertEquals(2, $stats['review_count']);
    }

    public function test_it_excludes_rejected_reviews_from_calculation()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // Create 2 approved reviews
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        
        // Create 1 rejected review (should not be included)
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 1,
            'status' => MenuItemReview::STATUS_REJECTED,
        ]);

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals(4.0, $stats['average_rating']); // (5+3)/2 = 4.0, rejected review excluded
        $this->assertEquals(2, $stats['review_count']);
    }

    public function test_it_returns_null_average_when_no_approved_reviews_exist()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertNull($stats['average_rating']);
        $this->assertEquals(0, $stats['review_count']);
    }

    public function test_it_rounds_average_to_one_decimal_place()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // Create reviews with average 4.333... (5+4+4)/3
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals(4.3, $stats['average_rating']); // Should round to 4.3
    }

    public function test_it_calculates_rating_distribution_correctly()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // Create reviews: 2x5-star, 1x4-star, 3x3-star, 1x2-star, 0x1-star
        MenuItemReview::factory()->count(2)->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->count(3)->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 2,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals([
            1 => 0,
            2 => 1,
            3 => 3,
            4 => 1,
            5 => 2,
        ], $stats['rating_distribution']);
    }

    public function test_it_returns_zero_distribution_when_no_approved_reviews()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals([
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
        ], $stats['rating_distribution']);
    }

    public function test_it_caches_rating_statistics()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act - First call should cache
        $stats1 = $this->service->calculateRatingStats($menuItem->id);
        
        // Add another review but shouldn't affect cached result
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 1,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        
        // Second call should use cache
        $stats2 = $this->service->calculateRatingStats($menuItem->id);

        // Assert - Both should return same cached value
        $this->assertEquals($stats1, $stats2);
        $this->assertEquals(5.0, $stats2['average_rating']);
        $this->assertEquals(1, $stats2['review_count']); // Still 1 because cached
    }

    public function test_it_recalculates_and_updates_cache()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // First call to cache
        $stats1 = $this->service->calculateRatingStats($menuItem->id);
        
        // Add another review
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act - Recalculate should invalidate cache and compute fresh
        $stats2 = $this->service->recalculateForMenuItem($menuItem->id);

        // Assert
        $this->assertEquals(4.0, $stats2['average_rating']); // (5+3)/2 = 4.0
        $this->assertEquals(2, $stats2['review_count']);
    }

    public function test_it_invalidates_cache_correctly()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Cache the stats
        $this->service->calculateRatingStats($menuItem->id);

        // Act - Invalidate cache
        $this->service->invalidateCache($menuItem->id);
        
        // Add another review
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 1,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        
        // Get stats again (should recalculate)
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals(3.0, $stats['average_rating']); // (5+1)/2 = 3.0
        $this->assertEquals(2, $stats['review_count']);
    }

    public function test_it_calculates_rating_distribution_percentages()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        
        // Create 10 reviews for easy percentage calculation
        MenuItemReview::factory()->count(5)->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->count(3)->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->count(2)->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $percentages = $this->service->getRatingDistributionPercentages($menuItem->id);

        // Assert
        $this->assertEquals(50.0, $percentages[5]); // 5/10 = 50%
        $this->assertEquals(30.0, $percentages[4]); // 3/10 = 30%
        $this->assertEquals(20.0, $percentages[3]); // 2/10 = 20%
        $this->assertEquals(0.0, $percentages[2]);
        $this->assertEquals(0.0, $percentages[1]);
    }

    public function test_it_returns_zero_percentages_when_no_reviews()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();

        // Act
        $percentages = $this->service->getRatingDistributionPercentages($menuItem->id);

        // Assert
        $this->assertEquals([
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
        ], $percentages);
    }

    public function test_it_handles_bulk_recalculation()
    {
        // Arrange
        $menuItem1 = MenuItem::factory()->create();
        $menuItem2 = MenuItem::factory()->create();
        $menuItem3 = MenuItem::factory()->create();
        
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem1->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem2->id,
            'rating' => 3,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $result = $this->service->bulkRecalculate([
            $menuItem1->id,
            $menuItem2->id,
            $menuItem3->id, // No reviews
        ]);

        // Assert
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(5.0, $result['results'][$menuItem1->id]['average_rating']);
        $this->assertEquals(3.0, $result['results'][$menuItem2->id]['average_rating']);
        $this->assertNull($result['results'][$menuItem3->id]['average_rating']);
    }

    public function test_it_isolates_ratings_between_different_menu_items()
    {
        // Arrange
        $menuItem1 = MenuItem::factory()->create();
        $menuItem2 = MenuItem::factory()->create();
        
        // MenuItem1: 5-star review
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem1->id,
            'rating' => 5,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);
        
        // MenuItem2: 2-star review
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem2->id,
            'rating' => 2,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $stats1 = $this->service->calculateRatingStats($menuItem1->id);
        $stats2 = $this->service->calculateRatingStats($menuItem2->id);

        // Assert
        $this->assertEquals(5.0, $stats1['average_rating']);
        $this->assertEquals(1, $stats1['review_count']);
        
        $this->assertEquals(2.0, $stats2['average_rating']);
        $this->assertEquals(1, $stats2['review_count']);
    }

    public function test_it_handles_single_review_correctly()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        MenuItemReview::factory()->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 3,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals(3.0, $stats['average_rating']);
        $this->assertEquals(1, $stats['review_count']);
        $this->assertEquals([
            1 => 0,
            2 => 0,
            3 => 1,
            4 => 0,
            5 => 0,
        ], $stats['rating_distribution']);
    }

    public function test_it_handles_all_same_ratings()
    {
        // Arrange
        $menuItem = MenuItem::factory()->create();
        MenuItemReview::factory()->count(5)->create([
            'menu_item_id' => $menuItem->id,
            'rating' => 4,
            'status' => MenuItemReview::STATUS_APPROVED,
        ]);

        // Act
        $stats = $this->service->calculateRatingStats($menuItem->id);

        // Assert
        $this->assertEquals(4.0, $stats['average_rating']);
        $this->assertEquals(5, $stats['review_count']);
        $this->assertEquals([
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 5,
            5 => 0,
        ], $stats['rating_distribution']);
    }
}
