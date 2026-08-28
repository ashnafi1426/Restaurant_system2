<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Get approved reviews for a menu item with pagination.
     * No authentication required.
     */
    public function index(Request $request, string $menuItemId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            $sortBy = $request->query('sort', 'recent');
            
            $reviews = $this->reviewService->getPublicReviews($menuItemId, $perPage, $sortBy);
            
            // Transform reviews to public display format
            $reviews->getCollection()->transform(function ($review) {
                return $review->public_display_data;
            });
            
            return response()->json($reviews);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve reviews',
            ], 500);
        }
    }

    /**
     * Get review statistics for a menu item.
     * Returns: average rating, total reviews, rating distribution
     * No authentication required.
     */
    public function stats(Request $request, string $menuItemId): JsonResponse
    {
        try {
            $stats = $this->reviewService->getMenuItemStats($menuItemId);
            
            return response()->json($stats);
        } catch (\Exception $e) {
            // Return default stats on error
            return response()->json([
                'menu_item_id' => $menuItemId,
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [
                    '1' => 0,
                    '2' => 0,
                    '3' => 0,
                    '4' => 0,
                    '5' => 0,
                ]
            ]);
        }
    }
}
