<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Get review statistics for a specific menu item.
     */
    public function itemStats(string $id): JsonResponse
    {
        try {
            $stats = $this->analyticsService->getMenuItemStatistics($id);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve item review statistics', [
                'item_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve statistics',
            ], 500);
        }
    }

    /**
     * Get top-rated menu items based on customer reviews.
     */
    public function topRated(Request $request): JsonResponse
    {
        try {
            $minReviews = (int) $request->query('min_reviews', 5);
            $limit = (int) $request->query('limit', 20);

            $items = $this->analyticsService->getTopRatedItems($minReviews, $limit);

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve top-rated items', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve top-rated items',
            ], 500);
        }
    }

    /**
     * Get lowest-rated menu items based on customer reviews.
     */
    public function lowestRated(Request $request): JsonResponse
    {
        try {
            $minReviews = (int) $request->query('min_reviews', 5);
            $limit = (int) $request->query('limit', 20);

            $items = $this->analyticsService->getLowestRatedItems($minReviews, $limit);

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve lowest-rated items', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve lowest-rated items',
            ], 500);
        }
    }

    /**
     * Get count of reviews pending moderation.
     */
    public function pendingCount(): JsonResponse
    {
        try {
            $count = $this->analyticsService->getPendingReviewCount();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_count' => $count,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve pending review count', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve pending count',
            ], 500);
        }
    }

    /**
     * Get review submission trends over time.
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $period = (string) $request->query('period', 'daily');
            $limit = (int) $request->query('limit', 30);

            $trends = $this->analyticsService->getReviewTrends($period, $limit);

            return response()->json([
                'success' => true,
                'data' => $trends,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve review trends', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve trends',
            ], 500);
        }
    }

    /**
     * Get overall review statistics across the restaurant.
     */
    public function overall(): JsonResponse
    {
        try {
            $stats = $this->analyticsService->getOverallStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve overall review statistics', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve overall statistics',
            ], 500);
        }
    }
}
