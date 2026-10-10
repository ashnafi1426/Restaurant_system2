<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModerationController extends Controller
{
    public function __construct(
        protected ModerationService $moderationService
    ) {}

    /**
     * Display paginated reviews by moderation status.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $perPage = (int) $request->query('per_page', 20);

            $reviews = $this->moderationService->getReviewsByStatus($status, $perPage);

            return response()->json($reviews);
        } catch (Throwable $e) {
            Log::error('Moderation index error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve reviews',
            ], 500);
        }
    }

    /**
     * Approve a pending review.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $moderatorId = (string) $request->user()->id;
            $review = $this->moderationService->approveReview($id, $moderatorId);

            return response()->json([
                'success' => true,
                'message' => 'Review approved successfully',
                'data' => $review,
            ]);
        } catch (Throwable $e) {
            Log::error('Moderation approve error', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to approve review',
            ], 500);
        }
    }

    /**
     * Reject a pending review.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $moderatorId = (string) $request->user()->id;
            $review = $this->moderationService->rejectReview($id, $moderatorId);

            return response()->json([
                'success' => true,
                'message' => 'Review rejected successfully',
                'data' => $review,
            ]);
        } catch (Throwable $e) {
            Log::error('Moderation reject error', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to reject review',
            ], 500);
        }
    }

    /**
     * Delete a review from moderation queue.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->moderationService->deleteReview($id);

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully',
            ], 200);
        } catch (Throwable $e) {
            Log::error('Moderation delete error', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to delete review',
            ], 500);
        }
    }

    /**
     * Get review moderation statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->moderationService->getModerationStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Throwable $e) {
            Log::error('Moderation stats error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve statistics',
            ], 500);
        }
    }
}

