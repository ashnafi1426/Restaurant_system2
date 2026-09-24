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

    public function createReview(array $data): MenuItemReview
    {
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        $this->purchaseVerificationService->verifyPurchase(
            $data['guest_id'],
            $data['menu_item_id'],
            $data['order_id']
        );

        $this->purchaseVerificationService->checkDuplicateReview(
            $data['guest_id'],
            $data['menu_item_id'],
            $data['order_id']
        );

        $review = MenuItemReview::create([
            'guest_id' => $data['guest_id'],
            'order_id' => $data['order_id'],
            'menu_item_id' => $data['menu_item_id'],
            'rating' => $data['rating'],
            'review_text' => $data['review_text'] ?? null,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        $review->load('guest', 'menuItem');

        $this->notificationService->notifyModeratorsOfNewReview($review);

        return $review;
    }

    public function updateReview(string $reviewId, string $guestId, array $data): MenuItemReview
    {
        $review = MenuItemReview::find($reviewId);

        if (!$review) {
            throw new \InvalidArgumentException('Review not found');
        }

        if ($review->guest_id !== $guestId) {
            throw new UnauthorizedReviewAccessException();
        }

        if (!$review->canBeModifiedByGuest()) {
            throw new ReviewNotModifiableException();
        }

        if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        if (isset($data['review_text']) && strlen($data['review_text']) > 1000) {
            throw new \InvalidArgumentException('Review text must not exceed 1000 characters');
        }

        $review->update([
            'rating' => $data['rating'] ?? $review->rating,
            'review_text' => $data['review_text'] ?? $review->review_text,
        ]);

        return $review->fresh();
    }

    public function deleteReview(string $reviewId, string $guestId): bool
    {
        $review = MenuItemReview::find($reviewId);

        if (!$review) {
            return false;
        }

        if ($review->guest_id !== $guestId) {
            throw new UnauthorizedReviewAccessException();
        }

        if (!$review->canBeModifiedByGuest()) {
            throw new ReviewNotModifiableException();
        }

        return $review->delete();
    }

    public function getEligibleItems(string $guestId)
    {
        $guest = Guest::find($guestId);

        if (!$guest) {
            return collect([]);
        }

        return $guest->getEligibleMenuItemsForReview();
    }

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

    public function createGuestReview(array $data): MenuItemReview
    {
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        $guest = Guest::where('email', $data['guest_email'])->first();
        
        if (!$guest) {
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

        $review = MenuItemReview::create([
            'guest_id' => $guest->id,
            'order_id' => $data['order_id'] ?? null,
            'menu_item_id' => $data['menu_item_id'],
            'rating' => $data['rating'],
            'review_text' => $data['review_text'] ?? null,
            'status' => MenuItemReview::STATUS_PENDING,
        ]);

        $review->load('guest', 'menuItem');

        $this->notificationService->notifyModeratorsOfNewReview($review);

        return $review;
    }

    public function getMenuItemStats(string $menuItemId): array
    {
        $reviews = MenuItemReview::where('menu_item_id', $menuItemId)
            ->approved()
            ->get();

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

        $averageRating = round($reviews->avg('rating'), 1);

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