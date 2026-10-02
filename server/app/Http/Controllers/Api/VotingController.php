<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DuplicateVoteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VoteRequest;
use App\Services\VotingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class VotingController extends Controller
{
    public function __construct(
        protected VotingService $votingService
    ) {}

    /**
     * Record helpful/not helpful vote on a review.
     */
    public function vote(VoteRequest $request, string $reviewId): JsonResponse
    {
        try {
            $voteType = $request->validated()['vote_type'];
            $guestId = $request->input('guest_id');
            $ipAddress = $request->ip();

            $vote = ($voteType === 'helpful')
                ? $this->votingService->voteHelpful($reviewId, $guestId, $ipAddress)
                : $this->votingService->voteNotHelpful($reviewId, $guestId, $ipAddress);

            $counts = $this->votingService->getVoteCounts($reviewId);

            return response()->json([
                'success' => true,
                'message' => 'Vote recorded successfully',
                'data' => [
                    'vote' => $vote,
                    'counts' => $counts,
                ],
            ]);
        } catch (DuplicateVoteException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Duplicate vote',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Vote recording error', ['review_id' => $reviewId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to record vote',
            ], 500);
        }
    }

    /**
     * Get aggregate helpful / not-helpful vote counts for a review.
     */
    public function getCounts(string $reviewId): JsonResponse
    {
        try {
            $counts = $this->votingService->getVoteCounts($reviewId);

            return response()->json([
                'success' => true,
                'data' => $counts,
            ]);
        } catch (Throwable $e) {
            Log::error('Get vote counts error', ['review_id' => $reviewId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve vote counts',
            ], 500);
        }
    }
}
