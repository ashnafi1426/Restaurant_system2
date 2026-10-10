<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublicReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Display paginated public reviews for a specific menu item.
     */
    public function index(Request $request, string $menuItemId): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 10);
            $sortBy = (string) $request->query('sort', 'recent');

            $reviews = $this->reviewService->getPublicReviews($menuItemId, $perPage, $sortBy);

            $reviews->getCollection()->transform(function ($review) {
                return $review->public_display_data;
            });

            return response()->json($reviews);
        } catch (Throwable $e) {
            Log::error('Get public reviews exception', [
                'menu_item_id' => $menuItemId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve reviews',
            ], 500);
        }
    }

    /**
     * Get aggregate review stats and rating distribution for a menu item.
     */
    public function stats(Request $request, string $menuItemId): JsonResponse
    {
        try {
            $stats = $this->reviewService->getMenuItemStats($menuItemId);

            return response()->json($stats);
        } catch (Throwable $e) {
            Log::error('Get menu item review stats exception', [
                'menu_item_id' => $menuItemId,
                'error' => $e->getMessage(),
            ]);

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
                ],
            ]);
        }
    }
}

