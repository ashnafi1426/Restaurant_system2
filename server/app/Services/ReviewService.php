<?php

namespace App\Services;

use App\Models\MenuItemReview;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Exceptions\ReviewNotModifiableException;
use App\Exceptions\UnauthorizedReviewAccessException;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    protected PurchaseVerificationService $purchaseVerificationService;
    protected NotificationService $notificationService;
    protected RatingCalculationService $ratingCalculationService;

    public function __construct(
        PurchaseVerificationService $purchaseVerificationService,
        NotificationService $notificationService,
        RatingCalculationService $ratingCalculationService
    ) {
        $this->purchaseVerificationService = $purchaseVerificationService;
        $this->notificationService = $notificationService;
        $this->ratingCalculationService = $ratingCalculationService;
    }

    public function createReview(array $data): MenuItemReview
    {
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        $menuItem = MenuItem::findOrFail($data['menu_item_id']);
        $hotelId = $menuItem->hotel_id ?? app(TenantContext::class)->getHotelId();

        $guestId = $data['guest_id'] ?? null;
        if (!$guestId && auth()->check()) {
            $user = auth()->user();
            $guest = Guest::where('email', $user->email)->first();
            if (!$guest) {
                $nameParts = explode(' ', $user->name ?? 'Guest', 2);
                $guest = Guest::create([
                    'hotel_id' => $hotelId,
                    'first_name' => $nameParts[0] ?? 'Guest',
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                ]);
            }
            $guestId = $guest->id;
        }

        if (!$guestId) {
            $guest = Guest::create([
                'hotel_id' => $hotelId,
                'first_name' => 'Guest',
                'last_name' => '',
                'email' => 'guest_' . substr(uniqid(), -6) . '@guest.local',
            ]);
            $guestId = $guest->id;
        }

        $orderId = null;
        if (!empty($data['order_id']) && Order::where('id', $data['order_id'])->exists()) {
            $orderId = $data['order_id'];
        }

        // Real-world rating system: if review exists from this guest for this item, update it; otherwise create it.
        $review = MenuItemReview::where('guest_id', $guestId)
            ->where('menu_item_id', $data['menu_item_id'])
            ->first();

        if ($review) {
            $review->update([
                'order_id' => $orderId ?? $review->order_id,
                'rating' => $data['rating'],
                'review_text' => $data['review_text'] ?? $review->review_text,
                'status' => MenuItemReview::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        } else {
            $review = MenuItemReview::create([
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'order_id' => $orderId,
                'menu_item_id' => $data['menu_item_id'],
                'rating' => $data['rating'],
                'review_text' => $data['review_text'] ?? null,
                'status' => MenuItemReview::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        }

        // Instantly recalculate menu item rating stats and invalidate menu caches
        $this->ratingCalculationService->recalculateForMenuItem($data['menu_item_id']);
        MenuService::invalidateMenuCache($hotelId);

        $review->load('guest', 'menuItem');

        // Optional notification
        try {
            $this->notificationService->notifyModeratorsOfNewReview($review);
        } catch (\Throwable $e) {
            // Non-blocking notification failure
        }

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

        $this->ratingCalculationService->recalculateForMenuItem($review->menu_item_id);

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

        $menuItemId = $review->menu_item_id;
        $deleted = $review->delete();

        if ($deleted) {
            $this->ratingCalculationService->recalculateForMenuItem($menuItemId);
        }

        return (bool) $deleted;
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

        $menuItem = MenuItem::findOrFail($data['menu_item_id']);
        $hotelId = $menuItem->hotel_id ?? app(TenantContext::class)->getHotelId();

        $guestEmail = !empty($data['guest_email']) ? trim($data['guest_email']) : null;
        $guestName = !empty($data['guest_name']) ? trim($data['guest_name']) : 'Guest';

        $guest = null;
        if ($guestEmail) {
            $guest = Guest::where('email', $guestEmail)->first();
        }
        
        if (!$guest) {
            $nameParts = explode(' ', $guestName, 2);
            $firstName = !empty($nameParts[0]) ? $nameParts[0] : 'Guest';
            $lastName = $nameParts[1] ?? '';
            $email = $guestEmail ?: ('guest_' . substr(uniqid(), -6) . '@guest.local');
            
            $guest = Guest::create([
                'hotel_id' => $hotelId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $data['guest_phone'] ?? '',
            ]);
        }

        $orderId = null;
        if (!empty($data['order_id']) && Order::where('id', $data['order_id'])->exists()) {
            $orderId = $data['order_id'];
        }

        // Real-world rating system: if review exists from this guest for this item, update it; otherwise create it.
        $review = MenuItemReview::where('guest_id', $guest->id)
            ->where('menu_item_id', $data['menu_item_id'])
            ->first();

        if ($review) {
            $review->update([
                'order_id' => $orderId ?? $review->order_id,
                'rating' => $data['rating'],
                'review_text' => $data['review_text'] ?? $review->review_text,
                'status' => MenuItemReview::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        } else {
            $review = MenuItemReview::create([
                'hotel_id' => $hotelId,
                'guest_id' => $guest->id,
                'order_id' => $orderId,
                'menu_item_id' => $data['menu_item_id'],
                'rating' => $data['rating'],
                'review_text' => $data['review_text'] ?? null,
                'status' => MenuItemReview::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        }

        // Instantly recalculate menu item rating stats and invalidate menu caches
        $this->ratingCalculationService->recalculateForMenuItem($data['menu_item_id']);
        MenuService::invalidateMenuCache($hotelId);

        $review->load('guest', 'menuItem');

        try {
            $this->notificationService->notifyModeratorsOfNewReview($review);
        } catch (\Throwable $e) {
            // Non-blocking notification failure
        }

        return $review;
    }

    public function getPublicReviews(string $menuItemId, int $perPage = 10, string $sortBy = 'recent')
    {
        $query = MenuItemReview::where('menu_item_id', $menuItemId)
            ->where('status', MenuItemReview::STATUS_APPROVED)
            ->with(['guest', 'response.responder']);

        if ($sortBy === 'helpful') {
            $query->byHelpfulness();
        } else {
            $query->recentFirst();
        }

        return $query->paginate($perPage);
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
                ],
                'rating_percentages' => [
                    '1' => 0,
                    '2' => 0,
                    '3' => 0,
                    '4' => 0,
                    '5' => 0,
                ],
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

        $percentages = [];
        foreach ($ratingDistribution as $star => $count) {
            $percentages[$star] = $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0;
        }

        return [
            'menu_item_id' => $menuItemId,
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution,
            'rating_percentages' => $percentages,
        ];
    }
}