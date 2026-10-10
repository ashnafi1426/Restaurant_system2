<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DuplicateReviewException;
use App\Exceptions\PurchaseNotVerifiedException;
use App\Exceptions\ReviewNotModifiableException;
use App\Exceptions\UnauthorizedReviewAccessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Store a verified review for a menu item.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        try {
            $review = $this->reviewService->createReview($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => $review->load(['guest', 'menuItem', 'order']),
            ], 201);
        } catch (PurchaseNotVerifiedException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Purchase not verified',
                'message' => $e->getMessage(),
            ], 422);
        } catch (DuplicateReviewException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Duplicate review',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Create review exception', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to create review',
            ], 500);
        }
    }

    /**
     * Store a guest review directly from public ordering portal.
     */
    public function storeGuest(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'nullable|string',
                'menu_item_id' => 'required|string|exists:menu_items,id',
                'rating' => 'required|integer|between:1,5',
                'review_text' => 'nullable|string|max:1000',
                'guest_name' => 'nullable|string|max:255',
                'guest_email' => 'nullable|email|max:255',
                'guest_phone' => 'nullable|string|max:50',
            ]);

            $review = $this->reviewService->createGuestReview($validated);

            return response()->json([
                'success' => true,
                'message' => trans_msg('review_submitted', default: 'Review submitted and published successfully'),
                'data' => $review,
            ], 201);
        } catch (PurchaseNotVerifiedException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Purchase not verified',
                'message' => $e->getMessage(),
            ], 422);
        } catch (DuplicateReviewException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Duplicate review',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Create guest review exception', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to create review: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified review.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $review = $this->reviewService->getReview($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Review not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $review,
            ]);
        } catch (Throwable $e) {
            Log::error('Get review exception', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve review',
            ], 500);
        }
    }

    /**
     * Update an existing review.
     */
    public function update(UpdateReviewRequest $request, string $id): JsonResponse
    {
        try {
            $guestId = $request->input('guest_id');
            $review = $this->reviewService->updateReview($id, $guestId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => $review,
            ]);
        } catch (ReviewNotModifiableException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Modification forbidden',
                'message' => $e->getMessage(),
            ], 422);
        } catch (UnauthorizedReviewAccessException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ], 403);
        } catch (Throwable $e) {
            Log::error('Update review exception', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to update review',
            ], 500);
        }
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $guestId = $request->input('guest_id');
            $deleted = $this->reviewService->deleteReview($id, $guestId);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Review not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => trans_msg('review_deleted', default: 'Review deleted successfully'),
            ], 200);
        } catch (ReviewNotModifiableException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Modification forbidden',
                'message' => $e->getMessage(),
            ], 422);
        } catch (UnauthorizedReviewAccessException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ], 403);
        } catch (Throwable $e) {
            Log::error('Delete review exception', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to delete review',
            ], 500);
        }
    }

    /**
     * Get menu items eligible for review by a specific guest.
     */
    public function eligibleItems(string $guestId): JsonResponse
    {
        try {
            $items = $this->reviewService->getEligibleItems($guestId);

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (Throwable $e) {
            Log::error('Get eligible items exception', ['guest_id' => $guestId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => 'Unable to retrieve eligible items',
            ], 500);
        }
    }
}

