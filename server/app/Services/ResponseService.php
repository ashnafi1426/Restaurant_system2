<?php

namespace App\Services;

use App\Models\ReviewResponse;
use App\Models\MenuItemReview;

class ResponseService
{
    /**
     * Create a management response to a review.
     *
     * @param string $reviewId
     * @param string $responderId
     * @param string $responseText
     * @return ReviewResponse
     * @throws \InvalidArgumentException
     */
    public function createResponse(string $reviewId, string $responderId, string $responseText): ReviewResponse
    {
        $review = MenuItemReview::findOrFail($reviewId);

        // Only approved reviews can have responses
        if (!$review->isApproved()) {
            throw new \InvalidArgumentException('Only approved reviews can have responses');
        }

        // Check if response already exists
        if ($review->response()->exists()) {
            throw new \InvalidArgumentException('This review already has a response. Use update instead.');
        }

        // Validate response text length
        if (strlen($responseText) > 500) {
            throw new \InvalidArgumentException('Response text must not exceed 500 characters');
        }

        return ReviewResponse::create([
            'review_id' => $reviewId,
            'responder_id' => $responderId,
            'response_text' => $responseText,
        ]);
    }

    /**
     * Update an existing response.
     *
     * @param string $responseId
     * @param string $responseText
     * @return ReviewResponse
     * @throws \InvalidArgumentException
     */
    public function updateResponse(string $responseId, string $responseText): ReviewResponse
    {
        $response = ReviewResponse::findOrFail($responseId);

        // Validate response text length
        if (strlen($responseText) > 500) {
            throw new \InvalidArgumentException('Response text must not exceed 500 characters');
        }

        $response->update([
            'response_text' => $responseText,
        ]);

        return $response->fresh();
    }

    /**
     * Delete a response.
     *
     * @param string $responseId
     * @return bool
     */
    public function deleteResponse(string $responseId): bool
    {
        $response = ReviewResponse::findOrFail($responseId);
        return $response->delete();
    }

    /**
     * Get response by review ID.
     *
     * @param string $reviewId
     * @return ReviewResponse|null
     */
    public function getResponseByReviewId(string $reviewId): ?ReviewResponse
    {
        return ReviewResponse::where('review_id', $reviewId)
            ->with('responder')
            ->first();
    }
}