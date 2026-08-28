<?php

namespace App\Services;

use App\Models\Order;
use App\Models\MenuItemReview;
use App\Exceptions\PurchaseNotVerifiedException;
use App\Exceptions\DuplicateReviewException;

class PurchaseVerificationService
{
    /**
     * Verify that the guest has a completed order containing the menu item.
     *
     * @param string $guestId
     * @param string $menuItemId
     * @param string $orderId
     * @return void
     * @throws PurchaseNotVerifiedException
     */
    public function verifyPurchase(string $guestId, string $menuItemId, string $orderId): void
    {
        // Check if order exists and belongs to the guest
        $order = Order::where('id', $orderId)
            ->where('guest_id', $guestId)
            ->first();
        
        if (!$order) {
            throw new PurchaseNotVerifiedException('You must order this item before reviewing it');
        }
        
        // Check if order is completed
        if (!$order->isCompleted()) {
            throw new PurchaseNotVerifiedException('You can only review items from completed orders');
        }
        
        // Check if order contains the menu item
        $hasMenuItem = $order->orderItems()
            ->where('menu_item_id', $menuItemId)
            ->exists();
        
        if (!$hasMenuItem) {
            throw new PurchaseNotVerifiedException('You must order this item before reviewing it');
        }
    }

    /**
     * Check if a review already exists for the given guest, menu item, and order combination.
     *
     * @param string $guestId
     * @param string $menuItemId
     * @param string $orderId
     * @return void
     * @throws DuplicateReviewException
     */
    public function checkDuplicateReview(string $guestId, string $menuItemId, string $orderId): void
    {
        $reviewExists = MenuItemReview::where('guest_id', $guestId)
            ->where('order_id', $orderId)
            ->where('menu_item_id', $menuItemId)
            ->exists();
        
        if ($reviewExists) {
            throw new DuplicateReviewException('You have already reviewed this item for this order');
        }
    }
}
