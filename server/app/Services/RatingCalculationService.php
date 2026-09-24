<?php

namespace App\Services;

use App\Models\MenuItemReview;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RatingCalculationService
{
    private const CACHE_PREFIX = 'menu_item_rating:';
    
    private const CACHE_TTL = 3600;

    public function calculateRatingStats(string $menuItemId): array
    {
        $cacheKey = self::CACHE_PREFIX . $menuItemId;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($menuItemId) {
            return $this->computeRatingStats($menuItemId);
        });
    }

    public function recalculateForMenuItem(string $menuItemId): array
    {
        $cacheKey = self::CACHE_PREFIX . $menuItemId;
        Cache::forget($cacheKey);
        
        $stats = $this->computeRatingStats($menuItemId);
        Cache::put($cacheKey, $stats, self::CACHE_TTL);
        
        return $stats;
    }

    private function computeRatingStats(string $menuItemId): array
    {
        $approvedReviews = MenuItemReview::where('menu_item_id', $menuItemId)
            ->where('status', MenuItemReview::STATUS_APPROVED)
            ->get();

        $reviewCount = $approvedReviews->count();
        
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

        $sum = $approvedReviews->sum('rating');
        $averageRating = round($sum / $reviewCount, 1);

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

    public function invalidateCache(string $menuItemId): void
    {
        $cacheKey = self::CACHE_PREFIX . $menuItemId;
        Cache::forget($cacheKey);
    }

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

    public function clearAllCaches(): void
    {
        $menuItemIds = MenuItem::pluck('id');
        
        foreach ($menuItemIds as $menuItemId) {
            $this->invalidateCache($menuItemId);
        }
    }
}
