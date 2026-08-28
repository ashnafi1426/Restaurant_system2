<?php

namespace App\Services;

use App\Models\ReviewNotification;
use App\Models\User;
use App\Models\MenuItemReview;

class NotificationService
{
    /**
     * Notify all moderators (managers and admins) of a new review submission.
     *
     * @param MenuItemReview $review
     * @return void
     */
    public function notifyModeratorsOfNewReview(MenuItemReview $review): void
    {
        // Get all users with manager or admin roles
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

    /**
     * Notify the guest who submitted a review about the moderation decision.
     *
     * @param MenuItemReview $review
     * @param string $decision 'approved' or 'rejected'
     * @return void
     */
    public function notifyGuestOfModeration(MenuItemReview $review, string $decision): void
    {
        // Get the guest (who is also a user in some systems, or might have a linked user account)
        // For now, we'll create notifications for the user system
        // If guests don't have user accounts, this would need to use email or another notification method
        
        $menuItemName = $review->menuItem->name ?? 'Unknown Item';
        
        $notificationType = $decision === 'approved' 
            ? ReviewNotification::TYPE_REVIEW_APPROVED 
            : ReviewNotification::TYPE_REVIEW_REJECTED;

        $message = $decision === 'approved'
            ? sprintf('Your review for %s has been approved and is now visible to other guests.', $menuItemName)
            : sprintf('Your review for %s has been rejected by moderation.', $menuItemName);

        // Only create notification if guest has a user account
        // This assumes guests might have user_id field or we skip this for pure guest accounts
        // Adjust based on your actual data model
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

    /**
     * Get unread notification count for a user.
     *
     * @param string $userId
     * @return int
     */
    public function getUnreadCount(string $userId): int
    {
        return ReviewNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark notification as read.
     *
     * @param string $notificationId
     * @return bool
     */
    public function markAsRead(string $notificationId): bool
    {
        $notification = ReviewNotification::find($notificationId);
        
        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Get all notifications for a user with pagination.
     *
     * @param string $userId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserNotifications(string $userId, int $perPage = 20)
    {
        return ReviewNotification::where('user_id', $userId)
            ->with('review.menuItem')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}