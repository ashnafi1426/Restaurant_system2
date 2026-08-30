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

    public function index(Request $request, string $menuItemId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            $sortBy = $request->query('sort', 'recent');
            
            $reviews = $this->reviewService->getPublicReviews($menuItemId, $perPage, $sortBy);
            
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

    public function stats(Request $request, string $menuItemId): JsonResponse
    {
        try {
            $stats = $this->reviewService->getMenuItemStats($menuItemId);
            
            return response()->json($stats);
        } catch (\Exception $e) {
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
