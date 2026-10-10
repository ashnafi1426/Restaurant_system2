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

    public function approveReview(string $reviewId, string $moderatorId): MenuItemReview
    {
        return DB::transaction(function () use ($reviewId, $moderatorId) {
            $review = MenuItemReview::findOrFail($reviewId);

            $review->update([
                'status' => MenuItemReview::STATUS_APPROVED,
                'approved_by' => $moderatorId,
                'approved_at' => now(),
            ]);

            $this->ratingCalculationService->recalculateForMenuItem($review->menu_item_id);

            $review->load('guest', 'menuItem');
            $this->notificationService->notifyGuestOfModeration($review, 'approved');

            return $review->fresh();
        });
    }

    public function rejectReview(string $reviewId, string $moderatorId): MenuItemReview
    {
        return DB::transaction(function () use ($reviewId, $moderatorId) {
            $review = MenuItemReview::findOrFail($reviewId);

            $review->update([
                'status' => MenuItemReview::STATUS_REJECTED,
                'rejected_by' => $moderatorId,
                'rejected_at' => now(),
            ]);

            $this->ratingCalculationService->recalculateForMenuItem($review->menu_item_id);

            $review->load('guest', 'menuItem');
            $this->notificationService->notifyGuestOfModeration($review, 'rejected');

            return $review->fresh();
        });
    }

    public function deleteReview(string $reviewId): bool
    {
        return DB::transaction(function () use ($reviewId) {
            $review = MenuItemReview::findOrFail($reviewId);
            $menuItemId = $review->menu_item_id;

            $deleted = $review->delete();

            if ($deleted) {
                $this->ratingCalculationService->recalculateForMenuItem($menuItemId);
            }

            return $deleted;
        });
    }

    public function getReviewsByStatus(?string $status = null, int $perPage = 20)
    {
        $query = MenuItemReview::with(['guest', 'menuItem', 'order']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

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

