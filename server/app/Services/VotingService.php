<?php

namespace App\Services;

use App\Models\ReviewHelpfulnessVote;
use App\Models\MenuItemReview;
use App\Exceptions\DuplicateVoteException;
use Illuminate\Support\Facades\DB;

class VotingService
{
    /**
     * Vote a review as helpful.
     *
     * @param string $reviewId
     * @param string|null $guestId
     * @param string|null $ipAddress
     * @return ReviewHelpfulnessVote
     * @throws DuplicateVoteException
     */
    public function voteHelpful(string $reviewId, ?string $guestId = null, ?string $ipAddress = null): ReviewHelpfulnessVote
    {
        return $this->recordVote($reviewId, ReviewHelpfulnessVote::VOTE_HELPFUL, $guestId, $ipAddress);
    }

    /**
     * Vote a review as not helpful.
     *
     * @param string $reviewId
     * @param string|null $guestId
     * @param string|null $ipAddress
     * @return ReviewHelpfulnessVote
     * @throws DuplicateVoteException
     */
    public function voteNotHelpful(string $reviewId, ?string $guestId = null, ?string $ipAddress = null): ReviewHelpfulnessVote
    {
        return $this->recordVote($reviewId, ReviewHelpfulnessVote::VOTE_NOT_HELPFUL, $guestId, $ipAddress);
    }

    /**
     * Record a vote (helpful or not helpful).
     *
     * @param string $reviewId
     * @param string $voteType
     * @param string|null $guestId
     * @param string|null $ipAddress
     * @return ReviewHelpfulnessVote
     * @throws DuplicateVoteException
     */
    private function recordVote(string $reviewId, string $voteType, ?string $guestId, ?string $ipAddress): ReviewHelpfulnessVote
    {
        return DB::transaction(function () use ($reviewId, $voteType, $guestId, $ipAddress) {
            // Check for duplicate vote
            $this->checkDuplicateVote($reviewId, $guestId, $ipAddress);

            // Create the vote
            $vote = ReviewHelpfulnessVote::create([
                'review_id' => $reviewId,
                'guest_id' => $guestId,
                'ip_address' => $ipAddress,
                'vote_type' => $voteType,
            ]);

            // Update the review's vote counts
            $this->updateVoteCounts($reviewId);

            return $vote;
        });
    }

    /**
     * Check if a vote already exists for this guest/IP.
     *
     * @param string $reviewId
     * @param string|null $guestId
     * @param string|null $ipAddress
     * @return void
     * @throws DuplicateVoteException
     */
    private function checkDuplicateVote(string $reviewId, ?string $guestId, ?string $ipAddress): void
    {
        $query = ReviewHelpfulnessVote::where('review_id', $reviewId);

        if ($guestId) {
            $query->where('guest_id', $guestId);
        } elseif ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }

        if ($query->exists()) {
            throw new DuplicateVoteException();
        }
    }

    /**
     * Update vote counts on the review.
     *
     * @param string $reviewId
     * @return void
     */
    private function updateVoteCounts(string $reviewId): void
    {
        $review = MenuItemReview::findOrFail($reviewId);

        $helpfulCount = ReviewHelpfulnessVote::where('review_id', $reviewId)
            ->where('vote_type', ReviewHelpfulnessVote::VOTE_HELPFUL)
            ->count();

        $notHelpfulCount = ReviewHelpfulnessVote::where('review_id', $reviewId)
            ->where('vote_type', ReviewHelpfulnessVote::VOTE_NOT_HELPFUL)
            ->count();

        $review->update([
            'helpful_count' => $helpfulCount,
            'not_helpful_count' => $notHelpfulCount,
        ]);
    }

    /**
     * Get vote counts for a review.
     *
     * @param string $reviewId
     * @return array
     */
    public function getVoteCounts(string $reviewId): array
    {
        $review = MenuItemReview::findOrFail($reviewId);

        return [
            'helpful_count' => $review->helpful_count,
            'not_helpful_count' => $review->not_helpful_count,
            'helpfulness_ratio' => $review->helpfulness_ratio,
        ];
    }
}