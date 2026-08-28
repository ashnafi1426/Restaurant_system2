<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get detailed statistics for a menu item.
     */
    public function itemStats(string $id): JsonResponse
    {
        try {
            $stats = $this->analyticsService->getMenuItemStatistics($id);
            
            return response()->json([
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve statistics',
            ], 500);
        }
    }

    /**
     * Get top-rated menu items.
     */
    public function topRated(Request $request): JsonResponse
    {
        try {
            $minReviews = $request->query('min_reviews', 5);
            $limit = $request->query('limit', 20);
            
            $items = $this->analyticsService->getTopRatedItems($minReviews, $limit);
            
            return response()->json([
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve top-rated items',
            ], 500);
        }
    }

    /**
     * Get lowest-rated menu items.
     */
    public function lowestRated(Request $request): JsonResponse
    {
        try {
            $minReviews = $request->query('min_reviews', 5);
            $limit = $request->query('limit', 20);
            
            $items = $this->analyticsService->getLowestRatedItems($minReviews, $limit);
            
            return response()->json([
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve lowest-rated items',
            ], 500);
        }
    }

    /**
     * Get pending review count.
     */
    public function pendingCount(): JsonResponse
    {
        try {
            $count = $this->analyticsService->getPendingReviewCount();
            
            return response()->json([
                'data' => [
                    'pending_count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve pending count',
            ], 500);
        }
    }

    /**
     * Get review submission trends.
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'daily');
            $limit = $request->query('limit', 30);
            
            $trends = $this->analyticsService->getReviewTrends($period, $limit);
            
            return response()->json([
                'data' => $trends,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve trends',
            ], 500);
        }
    }

    /**
     * Get overall review system statistics.
     */
    public function overall(): JsonResponse
    {
        try {
            $stats = $this->analyticsService->getOverallStatistics();
            
            return response()->json([
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve overall statistics',
            ], 500);
        }
    }
}
