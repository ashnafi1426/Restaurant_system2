<?php

namespace App\Services;

use App\Models\Order;
use App\Models\MenuItemReview;
use App\Exceptions\PurchaseNotVerifiedException;
use App\Exceptions\DuplicateReviewException;

class PurchaseVerificationService
{
    public function verifyPurchase(string $guestId, string $menuItemId, string $orderId): void
    {
        $order = Order::where('id', $orderId)
            ->where('guest_id', $guestId)
            ->first();

        if (!$order) {
            throw new PurchaseNotVerifiedException('You must order this item before reviewing it');
        }

        if (!$order->isCompleted()) {
            throw new PurchaseNotVerifiedException('You can only review items from completed orders');
        }

        $hasMenuItem = $order->orderItems()
            ->where('menu_item_id', $menuItemId)
            ->exists();

        if (!$hasMenuItem) {
            throw new PurchaseNotVerifiedException('You must order this item before reviewing it');
        }
    }

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

