<?php

namespace App\Services;

use App\Models\MenuItemReview;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RatingCalculationService
{
    /**
     * Cache key prefix for menu item ratings
     */
    private const CACHE_PREFIX = 'menu_item_rating:';
    
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Calculate rating statistics for a menu item.
     * Returns average rating, review count, and rating distribution.
     *
     * @param string $menuItemId
     * @return array
     */
    public function calculateRatingStats(string $menuItemId): array
    {
        // Try to get from cache first
        $cacheKey = self::CACHE_PREFIX . $menuItemId;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($menuItemId) {
            return $this->computeRatingStats($menuItemId);
        });
    }

    /**
     * Recalculate rating statistics for a menu item and update cache.
     * This method should be called when a review status changes.
     *
     * @param string $menuItemId
     * @return array
     */
    public function recalculateForMenuItem(string $menuItemId): array
    {
        // Invalidate cache
        $cacheKey = self::CACHE_PREFIX . $menuItemId;
        Cache::forget($cacheKey);
        
        // Recalculate and cache
        $stats = $this->computeRatingStats($menuItemId);
        Cache::put($cacheKey, $stats, self::CACHE_TTL);
        
        return $stats;
    }

    /**
     * Compute rating statistics from the database.
     * This is the actual calculation logic.
     *
     * @param string $menuItemId
     * @return array
     */
    private function computeRatingStats(string $menuItemId): array
    {
        // Get only approved reviews for this menu item
        $approvedReviews = MenuItemReview::where('menu_item_id', $menuItemId)
            ->where('status', MenuItemReview::STATUS_APPROVED)
            ->get();

        $reviewCount = $approvedReviews->count();
        
        // If no approved reviews, return null for average rating
        if ($reviewCount === 0) {
            return [
                'average_rating' => null,
                'review_count' => 0,
                'rating_distribution' => [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                ],
            ];
        }

        // Calculate average rating (arithmetic mean, rounded to 1 decimal)
        $sum = $approvedReviews->sum('rating');
        $averageRating = round($sum / $reviewCount, 1);

        // Calculate rating distribution (count for each 1-5 star)
        $ratingDistribution = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
        ];

        foreach ($approvedReviews as $review) {
            $ratingDistribution[$review->rating]++;
        }

        return [
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'rating_distribution' => $ratingDistribution,
        ];
    }

    /**
     * Get rating distribution as percentages.
     *
     * @param string $menuItemId
     * @return array
     */
    public function getRatingDistributionPercentages(string $menuItemId): array
    {
        $stats = $this->calculateRatingStats($menuItemId);
        $reviewCount = $stats['review_count'];

        if ($reviewCount === 0) {
            return [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ];
        }

        $distribution = $stats['rating_distribution'];
        $percentages = [];

        foreach ($distribution as $rating => $count) {
            $percentages[$rating] = round(($count / $reviewCount) * 100, 1);
        }

        return $percentages;
    }

    /**
     * Invalidate cache for a specific menu item.
     *
     * @param string $menuItemId
     * @return void
     */
    public function invalidateCache(string $menuItemId): void
    {
        $cacheKey = self::CACHE_PREFIX . $menuItemId;
        Cache::forget($cacheKey);
    }

    /**
     * Bulk recalculate ratings for multiple menu items.
     * Useful for maintenance tasks or migrations.
     *
     * @param array $menuItemIds
     * @return array Statistics about the recalculation
     */
    public function bulkRecalculate(array $menuItemIds): array
    {
        $processed = 0;
        $results = [];

        foreach ($menuItemIds as $menuItemId) {
            try {
                $stats = $this->recalculateForMenuItem($menuItemId);
                $results[$menuItemId] = $stats;
                $processed++;
            } catch (\Exception $e) {
                $results[$menuItemId] = ['error' => $e->getMessage()];
            }
        }

        return [
            'total' => count($menuItemIds),
            'processed' => $processed,
            'failed' => count($menuItemIds) - $processed,
            'results' => $results,
        ];
    }

    /**
     * Clear all rating caches.
     * Use with caution - this clears all cached ratings.
     *
     * @return void
     */
    public function clearAllCaches(): void
    {
        // Get all menu item IDs and clear their caches
        $menuItemIds = MenuItem::pluck('id');
        
        foreach ($menuItemIds as $menuItemId) {
            $this->invalidateCache($menuItemId);
        }
    }
}
