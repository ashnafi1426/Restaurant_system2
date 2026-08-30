<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    protected ModerationService $moderationService;

    public function __construct(ModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $perPage = $request->query('per_page', 20);
            
            $reviews = $this->moderationService->getReviewsByStatus($status, $perPage);
            
            return response()->json($reviews);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve reviews',
            ], 500);
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $moderatorId = $request->user()->id;
            $review = $this->moderationService->approveReview($id, $moderatorId);
            
            return response()->json([
                'message' => 'Review approved successfully',
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to approve review',
            ], 500);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $moderatorId = $request->user()->id;
            $review = $this->moderationService->rejectReview($id, $moderatorId);
            
            return response()->json([
                'message' => 'Review rejected successfully',
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to reject review',
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->moderationService->deleteReview($id);
            
            return response()->json([
                'message' => 'Review deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to delete review',
            ], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->moderationService->getModerationStats();
            
            return response()->json([
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve statistics',
            ], 500);
        }
    }
}
