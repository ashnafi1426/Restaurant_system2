<?php

namespace App\Services;

use App\Models\ReviewHelpfulnessVote;
use App\Models\MenuItemReview;
use App\Exceptions\DuplicateVoteException;
use Illuminate\Support\Facades\DB;

class VotingService
{
    public function voteHelpful(string $reviewId, ?string $guestId = null, ?string $ipAddress = null): ReviewHelpfulnessVote
    {
        return $this->recordVote($reviewId, ReviewHelpfulnessVote::VOTE_HELPFUL, $guestId, $ipAddress);
    }

    public function voteNotHelpful(string $reviewId, ?string $guestId = null, ?string $ipAddress = null): ReviewHelpfulnessVote
    {
        return $this->recordVote($reviewId, ReviewHelpfulnessVote::VOTE_NOT_HELPFUL, $guestId, $ipAddress);
    }

    private function recordVote(string $reviewId, string $voteType, ?string $guestId, ?string $ipAddress): ReviewHelpfulnessVote
    {
        return DB::transaction(function () use ($reviewId, $voteType, $guestId, $ipAddress) {
            $this->checkDuplicateVote($reviewId, $guestId, $ipAddress);

            $vote = ReviewHelpfulnessVote::create([
                'review_id' => $reviewId,
                'guest_id' => $guestId,
                'ip_address' => $ipAddress,
                'vote_type' => $voteType,
            ]);

            $this->updateVoteCounts($reviewId);

            return $vote;
        });
    }

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