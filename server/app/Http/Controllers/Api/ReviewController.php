<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Services\ReviewService;
use App\Exceptions\PurchaseNotVerifiedException;
use App\Exceptions\DuplicateReviewException;
use App\Exceptions\ReviewNotModifiableException;
use App\Exceptions\UnauthorizedReviewAccessException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Create a new review.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        try {
            $review = $this->reviewService->createReview($request->validated());
            
            return response()->json([
                'message' => 'Review submitted successfully',
                'data' => $review->load(['guest', 'menuItem', 'order']),
            ], 201);
        } catch (PurchaseNotVerifiedException $e) {
            return response()->json([
                'error' => 'Purchase not verified',
                'message' => $e->getMessage(),
            ], 422);
        } catch (DuplicateReviewException $e) {
            return response()->json([
                'error' => 'Duplicate review',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to create review',
            ], 500);
        }
    }

    /**
     * Create a review from QR menu (no auth required - guest uses QR token).
     * This allows guests to write reviews without being logged in.
     */
    public function storeGuest(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'order_id' => 'nullable|uuid',  // Make order_id optional - not all QR guests have placed orders
                'menu_item_id' => 'required|uuid',
                'rating' => 'required|integer|between:1,5',
                'review_text' => 'required|string|min:10|max:500',
                'guest_name' => 'required|string',
                'guest_email' => 'required|email',
            ]);

            // Create a temporary guest user or use the order's guest
            $review = $this->reviewService->createGuestReview($validated);
            
            return response()->json([
                'message' => 'Review submitted successfully',
                'data' => $review,
            ], 201);
        } catch (PurchaseNotVerifiedException $e) {
            return response()->json([
                'error' => 'Purchase not verified',
                'message' => $e->getMessage(),
            ], 422);
        } catch (DuplicateReviewException $e) {
            return response()->json([
                'error' => 'Duplicate review',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to create review: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific review.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $review = $this->reviewService->getReview($id);
            
            if (!$review) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Review not found',
                ], 404);
            }

            return response()->json([
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve review',
            ], 500);
        }
    }

    /**
     * Update a review (guest owner only, pending only).
     */
    public function update(UpdateReviewRequest $request, string $id): JsonResponse
    {
        try {
            $guestId = $request->input('guest_id');
            $review = $this->reviewService->updateReview($id, $guestId, $request->validated());
            
            return response()->json([
                'message' => 'Review updated successfully',
                'data' => $review,
            ]);
        } catch (ReviewNotModifiableException $e) {
            return response()->json([
                'error' => 'Modification forbidden',
                'message' => $e->getMessage(),
            ], 422);
        } catch (UnauthorizedReviewAccessException $e) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to update review',
            ], 500);
        }
    }

    /**
     * Delete a review (guest owner only, pending only).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $guestId = $request->input('guest_id');
            $deleted = $this->reviewService->deleteReview($id, $guestId);
            
            if (!$deleted) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Review not found',
                ], 404);
            }

            return response()->json([
                'message' => 'Review deleted successfully',
            ], 204);
        } catch (ReviewNotModifiableException $e) {
            return response()->json([
                'error' => 'Modification forbidden',
                'message' => $e->getMessage(),
            ], 422);
        } catch (UnauthorizedReviewAccessException $e) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to delete review',
            ], 500);
        }
    }

    /**
     * Get eligible menu items that a guest can review.
     */
    public function eligibleItems(string $guestId): JsonResponse
    {
        try {
            $items = $this->reviewService->getEligibleItems($guestId);
            
            return response()->json([
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to retrieve eligible items',
            ], 500);
        }
    }
}
