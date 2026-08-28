<?php

namespace App\Services;

use App\Models\MenuItemReview;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Exceptions\PurchaseNotVerifiedException;
use App\Exceptions\DuplicateReviewException;
use App\Exceptions\ReviewNotModifiableException;
use App\Exceptions\UnauthorizedReviewAccessException;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    protected PurchaseVerificationService $purchaseVerificationService;
    protected NotificationService $notificationService;

    public function __construct(
        PurchaseVerificationService $purchaseVerificationService,
        NotificationService $notificationService
    ) {
        $this->purchaseVerificationService = $purchaseVerificationService;
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new review after verifying purchase and checking for duplicates.
     *
     * @param array $data
     * @return MenuItemReview
     * @throws PurchaseNotVerifiedException
     * @throws DuplicateReviewException
     * @throws \InvalidArgumentException
     */
    public function createReview(array $data): MenuItemReview
    {
        // Validate rating range
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        // Verify purchase
        $this->purchaseVerificationService->verifyPurchase(
            $data['guest_id'],
            $data['menu_item_id'],
            $data['order_id']
        );

        // Check for duplicate review
        $this->purchaseVerificationService->checkDuplicateReview(
            $data['guest_id'],
            $data['menu_item_id'],
            $data['order_id']
        );

        // Create review with pending status
        $review = MenuItemReview::create([
            'guest_id' => $data['guest_id'],
            'order_id' => $data['order_id'],
            'menu_item_id' => $data['menu_item_id'],
            'rating' => $data['rating'],
            'review_text' => $data['review_text'] ?? null,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Load relationships for notification
        $review->load('guest', 'menuItem');

        // Notify moderators
        $this->notificationService->notifyModeratorsOfNewReview($review);

        return $review;
    }

    /**
     * Update an existing review (only if it's pending and owned by the guest).
     *
     * @param string $reviewId
     * @param string $guestId
     * @param array $data
     * @return MenuItemReview
     * @throws ReviewNotModifiableException
     * @throws UnauthorizedReviewAccessException
     * @throws \InvalidArgumentException
     */
    public function updateReview(string $reviewId, string $guestId, array $data): MenuItemReview
    {
        $review = MenuItemReview::find($reviewId);

        if (!$review) {
            throw new \InvalidArgumentException('Review not found');
        }

        // Check ownership
        if ($review->guest_id !== $guestId) {
            throw new UnauthorizedReviewAccessException();
        }

        // Check if review can be modified (must be pending)
        if (!$review->canBeModifiedByGuest()) {
            throw new ReviewNotModifiableException();
        }

        // Validate rating if provided
        if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        // Validate review text length if provided
        if (isset($data['review_text']) && strlen($data['review_text']) > 1000) {
            throw new \InvalidArgumentException('Review text must not exceed 1000 characters');
        }

        // Update the review
        $review->update([
            'rating' => $data['rating'] ?? $review->rating,
            'review_text' => $data['review_text'] ?? $review->review_text,
        ]);

        return $review->fresh();
    }

    /**
     * Delete a review (only if it's pending and owned by the guest).
     *
     * @param string $reviewId
     * @param string $guestId
     * @return bool
     * @throws ReviewNotModifiableException
     * @throws UnauthorizedReviewAccessException
     */
    public function deleteReview(string $reviewId, string $guestId): bool
    {
        $review = MenuItemReview::find($reviewId);

        if (!$review) {
            return false;
        }

        // Check ownership
        if ($review->guest_id !== $guestId) {
            throw new UnauthorizedReviewAccessException();
        }

        // Check if review can be modified (must be pending)
        if (!$review->canBeModifiedByGuest()) {
            throw new ReviewNotModifiableException();
        }

        return $review->delete();
    }

    /**
     * Get eligible menu items that a guest can review.
     *
     * @param string $guestId
     * @return \Illuminate\Support\Collection
     */
    public function getEligibleItems(string $guestId)
    {
        $guest = Guest::find($guestId);

        if (!$guest) {
            return collect([]);
        }

        return $guest->getEligibleMenuItemsForReview();
    }

    /**
     * Get a review by ID with all relationships loaded.
     *
     * @param string $reviewId
     * @return MenuItemReview|null
     */
    public function getReview(string $reviewId): ?MenuItemReview
    {
        return MenuItemReview::with([
            'guest',
            'order',
            'menuItem',
            'response.responder',
            'votes'
        ])->find($reviewId);
    }

    /**
     * Create a guest review from QR menu (no authentication required).
     * Creates a temporary guest record if needed.
     * Note: order_id is optional for QR guests who haven't placed an order yet
     *
     * @param array $data
     * @return MenuItemReview
     * @throws \InvalidArgumentException
     */
    public function createGuestReview(array $data): MenuItemReview
    {
        // Validate rating range
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        // Get or create guest record
        $guest = Guest::where('email', $data['guest_email'])->first();
        
        if (!$guest) {
            // Split guest name into first and last name
            $nameParts = explode(' ', $data['guest_name'], 2);
            $firstName = $nameParts[0] ?? 'Guest';
            $lastName = $nameParts[1] ?? '';
            
            $guest = Guest::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $data['guest_email'],
                'phone' => $data['guest_phone'] ?? '',
            ]);
        }

        // Create review with pending status (no purchase verification for QR guests)
        // order_id is nullable for QR guests who haven't placed an order
        $review = MenuItemReview::create([
            'guest_id' => $guest->id,
            'order_id' => $data['order_id'] ?? null,  // Allow null for QR-only reviews
            'menu_item_id' => $data['menu_item_id'],
            'rating' => $data['rating'],
            'review_text' => $data['review_text'] ?? null,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        // Load relationships for notification
        $review->load('guest', 'menuItem');

        // Notify moderators
        $this->notificationService->notifyModeratorsOfNewReview($review);

        return $review;
    }

    /**
     * Get review statistics for a menu item.
     * Returns: average rating, total reviews, rating distribution
     *
     * @param string $menuItemId
     * @return array
     */
    public function getMenuItemStats(string $menuItemId): array
    {
        // Get all approved reviews for this menu item
        $reviews = MenuItemReview::where('menu_item_id', $menuItemId)
            ->approved()
            ->get();

        // Calculate statistics
        $totalReviews = $reviews->count();
        
        if ($totalReviews === 0) {
            return [
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
            ];
        }

        // Calculate average rating
        $averageRating = round($reviews->avg('rating'), 1);

        // Calculate rating distribution
        $ratingDistribution = [
            '1' => $reviews->where('rating', 1)->count(),
            '2' => $reviews->where('rating', 2)->count(),
            '3' => $reviews->where('rating', 3)->count(),
            '4' => $reviews->where('rating', 4)->count(),
            '5' => $reviews->where('rating', 5)->count(),
        ];

        return [
            'menu_item_id' => $menuItemId,
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution
        ];
    }
}