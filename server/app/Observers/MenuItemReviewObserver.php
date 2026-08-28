<?php

namespace App\Observers;

use App\Models\MenuItemReview;
use App\Services\RatingCalculationService;

class MenuItemReviewObserver
{
    /**
     * The rating calculation service instance.
     *
     * @var RatingCalculationService
     */
    protected RatingCalculationService $ratingService;

    /**
     * Create a new observer instance.
     *
     * @param  RatingCalculationService  $ratingService
     * @return void
     */
    public function __construct(RatingCalculationService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    /**
     * Handle the MenuItemReview "updated" event.
     * 
     * When a review's status changes to or from 'approved', trigger rating recalculation
     * for the associated menu item. This ensures the menu item's average rating and
     * review count stay accurate.
     * 
     * As per Requirement 2.7: "WHEN a review status changes, THE Review_System SHALL 
     * recalculate the Average_Rating for the Menu_Item"
     *
     * @param  \App\Models\MenuItemReview  $review
     * @return void
     */
    public function updated(MenuItemReview $review): void
    {
        // Check if the status field was changed
        if ($review->isDirty('status')) {
            $originalStatus = $review->getOriginal('status');
            $newStatus = $review->status;

            // Trigger recalculation if status changed to or from 'approved'
            // This affects the average rating calculation since only approved reviews count
            if ($originalStatus === MenuItemReview::STATUS_APPROVED || $newStatus === MenuItemReview::STATUS_APPROVED) {
                $this->ratingService->recalculateForMenuItem($review->menu_item_id);
            }
        }
    }
}
