<?php

namespace App\Services;

use App\Models\MenuItemReview;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    protected RatingCalculationService $ratingCalculationService;

    public function __construct(RatingCalculationService $ratingCalculationService)
    {
        $this->ratingCalculationService = $ratingCalculationService;
    }

    public function getMenuItemStatistics(string $menuItemId): array
    {
        $stats = $this->ratingCalculationService->calculateRatingStats($menuItemId);
        $percentages = $this->ratingCalculationService->getRatingDistributionPercentages($menuItemId);

        return array_merge($stats, [
            'rating_distribution_percentages' => $percentages,
        ]);
    }

    public function getTopRatedItems(int $minReviews = 5, int $limit = 20)
    {
        return MenuItem::select('menu_items.*')
            ->selectRaw('
                (SELECT AVG(rating) 
                 FROM menu_item_reviews 
                 WHERE menu_item_reviews.menu_item_id = menu_items.id 
                 AND menu_item_reviews.status = ?) as avg_rating
            ', [MenuItemReview::STATUS_APPROVED])
            ->selectRaw('
                (SELECT COUNT(*) 
                 FROM menu_item_reviews 
                 WHERE menu_item_reviews.menu_item_id = menu_items.id 
                 AND menu_item_reviews.status = ?) as review_count
            ', [MenuItemReview::STATUS_APPROVED])
            ->having('review_count', '>=', $minReviews)
            ->orderByDesc('avg_rating')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->avg_rating = round($item->avg_rating, 1);
                return $item;
            });
    }

    public function getLowestRatedItems(int $minReviews = 5, int $limit = 20)
    {
        return MenuItem::select('menu_items.*')
            ->selectRaw('
                (SELECT AVG(rating) 
                 FROM menu_item_reviews 
                 WHERE menu_item_reviews.menu_item_id = menu_items.id 
                 AND menu_item_reviews.status = ?) as avg_rating
            ', [MenuItemReview::STATUS_APPROVED])
            ->selectRaw('
                (SELECT COUNT(*) 
                 FROM menu_item_reviews 
                 WHERE menu_item_reviews.menu_item_id = menu_items.id 
                 AND menu_item_reviews.status = ?) as review_count
            ', [MenuItemReview::STATUS_APPROVED])
            ->having('review_count', '>=', $minReviews)
            ->orderBy('avg_rating', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->avg_rating = round($item->avg_rating, 1);
                return $item;
            });
    }

    public function getPendingReviewCount(): int
    {
        return MenuItemReview::pending()->count();
    }

    public function getReviewTrends(string $period = 'daily', int $limit = 30): array
    {
        $dateFormat = match ($period) {
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $trends = MenuItemReview::select(
            DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
            DB::raw('COUNT(*) as review_count'),
            DB::raw('AVG(rating) as avg_rating')
        )
            ->where('created_at', '>=', now()->subDays($limit * ($period === 'monthly' ? 30 : ($period === 'weekly' ? 7 : 1))))
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($trend) {
                $trend->avg_rating = round($trend->avg_rating, 1);
                return $trend;
            })
            ->reverse()
            ->values();

        return $trends->toArray();
    }

    public function getOverallStatistics(): array
    {
        return [
            'total_reviews' => MenuItemReview::count(),
            'pending_reviews' => MenuItemReview::pending()->count(),
            'approved_reviews' => MenuItemReview::approved()->count(),
            'rejected_reviews' => MenuItemReview::rejected()->count(),
            'average_rating' => round(MenuItemReview::approved()->avg('rating'), 1),
            'total_menu_items_reviewed' => MenuItemReview::approved()->distinct('menu_item_id')->count(),
        ];
    }
}