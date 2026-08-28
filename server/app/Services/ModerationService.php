<?php

namespace App\Services;

use App\Models\MenuItemReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ModerationService
{
    protected RatingCalculationService $ratingCalculationService;
    protected NotificationService $notificationService;

    public function __construct(
        RatingCalculationService $ratingCalculationService,
        NotificationService $notificationService
    ) {
        $this->ratingCalculationService = $ratingCalculationService;
        $this->notificationService = $notificationService;
    }

    /**
     * Approve a pending review.
     *
     * @param string $reviewId
     * @param string $moderatorId
     * @return MenuItemReview
     */
    public function approveReview(string $reviewId, string $moderatorId): MenuItemReview
    {
        return DB::transaction(function () use ($reviewId, $moderatorId) {
            $review = MenuItemReview::findOrFail($reviewId);

            // Update review status to approved
            $review->update([
                'status' => MenuItemReview::STATUS_APPROVED,
                'approved_by' => $moderatorId,
                'approved_at' => now(),
            ]);

            // Trigger rating recalculation for the menu item
            $this->ratingCalculationService->recalculateForMenuItem($review->menu_item_id);

            // Notify guest of approval
            $review->load('guest', 'menuItem');
            $this->notificationService->notifyGuestOfModeration($review, 'approved');

            return $review->fresh();
        });
    }

    /**
     * Reject a pending review.
     *
     * @param string $reviewId
     * @param string $moderatorId
     * @return MenuItemReview
     */
    public function rejectReview(string $reviewId, string $moderatorId): MenuItemReview
    {
        return DB::transaction(function () use ($reviewId, $moderatorId) {
            $review = MenuItemReview::findOrFail($reviewId);

            // Update review status to rejected
            $review->update([
                'status' => MenuItemReview::STATUS_REJECTED,
                'rejected_by' => $moderatorId,
                'rejected_at' => now(),
            ]);

            // Trigger rating recalculation for the menu item (in case it was previously approved)
            $this->ratingCalculationService->recalculateForMenuItem($review->menu_item_id);

            // Notify guest of rejection
            $review->load('guest', 'menuItem');
            $this->notificationService->notifyGuestOfModeration($review, 'rejected');

            return $review->fresh();
        });
    }

    /**
     * Permanently delete a review.
     *
     * @param string $reviewId
     * @return bool
     */
    public function deleteReview(string $reviewId): bool
    {
        return DB::transaction(function () use ($reviewId) {
            $review = MenuItemReview::findOrFail($reviewId);
            $menuItemId = $review->menu_item_id;

            // Delete the review
            $deleted = $review->delete();

            // Trigger rating recalculation for the menu item
            if ($deleted) {
                $this->ratingCalculationService->recalculateForMenuItem($menuItemId);
            }

            return $deleted;
        });
    }

    /**
     * Get reviews filtered by status with pagination.
     *
     * @param string|null $status
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReviewsByStatus(?string $status = null, int $perPage = 20)
    {
        $query = MenuItemReview::with(['guest', 'menuItem', 'order']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get review details with all relationships.
     *
     * @param string $reviewId
     * @return MenuItemReview
     */
    public function getReviewDetails(string $reviewId): MenuItemReview
    {
        return MenuItemReview::with([
            'guest',
            'order',
            'menuItem',
            'approver',
            'rejecter',
            'response',
            'votes'
        ])->findOrFail($reviewId);
    }

    /**
     * Get moderation statistics.
     *
     * @return array
     */
    public function getModerationStats(): array
    {
        return [
            'pending' => MenuItemReview::pending()->count(),
            'approved' => MenuItemReview::approved()->count(),
            'rejected' => MenuItemReview::rejected()->count(),
            'total' => MenuItemReview::count(),
        ];
    }
}