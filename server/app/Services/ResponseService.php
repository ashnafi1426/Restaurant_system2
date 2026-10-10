<?php

namespace App\Services;

use App\Models\ReviewResponse;
use App\Models\MenuItemReview;

class ResponseService
{
    public function createResponse(string $reviewId, string $responderId, string $responseText): ReviewResponse
    {
        $review = MenuItemReview::findOrFail($reviewId);

        if (!$review->isApproved()) {
            throw new \InvalidArgumentException('Only approved reviews can have responses');
        }

        if ($review->response()->exists()) {
            throw new \InvalidArgumentException('This review already has a response. Use update instead.');
        }

        if (strlen($responseText) > 500) {
            throw new \InvalidArgumentException('Response text must not exceed 500 characters');
        }

        return ReviewResponse::create([
            'review_id' => $reviewId,
            'responder_id' => $responderId,
            'response_text' => $responseText,
        ]);
    }

    public function updateResponse(string $responseId, string $responseText): ReviewResponse
    {
        $response = ReviewResponse::findOrFail($responseId);

        if (strlen($responseText) > 500) {
            throw new \InvalidArgumentException('Response text must not exceed 500 characters');
        }

        $response->update([
            'response_text' => $responseText,
        ]);

        return $response->fresh();
    }

    public function deleteResponse(string $responseId): bool
    {
        $response = ReviewResponse::findOrFail($responseId);
        return $response->delete();
    }

    public function getResponseByReviewId(string $reviewId): ?ReviewResponse
    {
        return ReviewResponse::where('review_id', $reviewId)
            ->with('responder')
            ->first();
    }
}

