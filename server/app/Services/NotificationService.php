<?php

namespace App\Services;

use App\Models\ReviewNotification;
use App\Models\User;
use App\Models\MenuItemReview;

class NotificationService
{
    public function notifyModeratorsOfNewReview(MenuItemReview $review): void
    {
        $moderators = User::whereIn('role', ['manager', 'admin'])->get();

        $guestName = $review->guest
            ? $review->guest->first_name . ' ' . $review->guest->last_name
            : 'Anonymous Guest';

        $menuItemName = $review->menuItem->name ?? 'Unknown Item';

        $message = sprintf(
            'New review submitted by %s for %s (Rating: %d/5)',
            $guestName,
            $menuItemName,
            $review->rating
        );

        foreach ($moderators as $moderator) {
            ReviewNotification::create([
                'user_id' => $moderator->id,
                'review_id' => $review->id,
                'notification_type' => ReviewNotification::TYPE_NEW_REVIEW,
                'message' => $message,
                'is_read' => false,
            ]);
        }
    }

    public function notifyGuestOfModeration(MenuItemReview $review, string $decision): void
    {
        $menuItemName = $review->menuItem->name ?? 'Unknown Item';

        $notificationType = $decision === 'approved'
            ? ReviewNotification::TYPE_REVIEW_APPROVED
            : ReviewNotification::TYPE_REVIEW_REJECTED;

        $message = $decision === 'approved'
            ? sprintf('Your review for %s has been approved and is now visible to other guests.', $menuItemName)
            : sprintf('Your review for %s has been rejected by moderation.', $menuItemName);

        if ($review->guest && method_exists($review->guest, 'user')) {
            $user = $review->guest->user;
            if ($user) {
                ReviewNotification::create([
                    'user_id' => $user->id,
                    'review_id' => $review->id,
                    'notification_type' => $notificationType,
                    'message' => $message,
                    'is_read' => false,
                ]);
            }
        }
    }

    public function getUnreadCount(string $userId): int
    {
        return ReviewNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(string $notificationId): bool
    {
        $notification = ReviewNotification::find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    public function getUserNotifications(string $userId, int $perPage = 20)
    {
        return ReviewNotification::where('user_id', $userId)
            ->with('review.menuItem')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

