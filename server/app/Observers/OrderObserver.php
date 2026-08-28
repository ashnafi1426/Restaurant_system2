<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\MenuItemReview;

class OrderObserver
{
    /**
     * Handle the Order "deleting" event.
     * 
     * Prevents deletion of orders that have approved reviews to maintain review integrity.
     * As per Requirement 7.3: "WHEN an Order is deleted, THE Review_System SHALL prevent 
     * deletion if approved reviews exist and return an error"
     *
     * @param  \App\Models\Order  $order
     * @return bool|null
     * @throws \Exception
     */
    public function deleting(Order $order): ?bool
    {
        // Check if the order has any approved reviews
        $hasApprovedReviews = MenuItemReview::where('order_id', $order->id)
            ->where('status', MenuItemReview::STATUS_APPROVED)
            ->exists();

        if ($hasApprovedReviews) {
            throw new \Exception(
                'Cannot delete order with approved reviews. Review data integrity must be maintained.'
            );
        }

        // Allow deletion to proceed if no approved reviews exist
        return true;
    }
}
