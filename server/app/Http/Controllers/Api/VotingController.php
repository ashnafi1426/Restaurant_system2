<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoteRequest;
use App\Services\VotingService;
use App\Exceptions\DuplicateVoteException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VotingController extends Controller
{
    protected VotingService $votingService;

    public function __construct(VotingService $votingService)
    {
        $this->votingService = $votingService;
    }

    /**
     * Record a helpfulness vote for a review.
     * No authentication required (supports anonymous voting).
     */
    public function vote(VoteRequest $request, string $reviewId): JsonResponse
    {
        try {
            $voteType = $request->validated()['vote_type'];
            $guestId = $request->input('guest_id');
            $ipAddress = $request->ip();
            
            if ($voteType === 'helpful') {
                $vote = $this->votingService->voteHelpful($reviewId, $guestId, $ipAddress);
            } else {
                $vote = $this->votingService->voteNotHelpful($reviewId, $guestId, $ipAddress);
            }
            
            // Get updated vote counts
            $counts = $this->votingService->getVoteCounts($reviewId);
            
            return response()->json([
                'message' => 'Vote recorded successfully',
                'data' => [
                    'vote' => $vote,
                    'counts' => $counts,
                ],
            ]);
        } catch (DuplicateVoteException $e) {
            return response()->json([
                'error' => 'Duplicate vote',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to record vote',
            ], 500);
        }
    }

    /**
     * Get vote counts for a review.
     */
    public function getCounts(string $reviewId): JsonResponse
    {
        try {
            $counts = $this->votingService->getVoteCounts($reviewId);
            
            return response()->json([
                'data' => $counts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve vote counts',
            ], 500);
        }
    }
}
