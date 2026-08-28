<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateResponseRequest;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Create a management response to a review.
     */
    public function store(CreateResponseRequest $request, string $reviewId): JsonResponse
    {
        try {
            $responderId = $request->user()->id;
            $response = $this->responseService->createResponse(
                $reviewId,
                $responderId,
                $request->validated()['response_text']
            );
            
            return response()->json([
                'message' => 'Response created successfully',
                'data' => $response->load('responder'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to create response',
            ], 500);
        }
    }

    /**
     * Update an existing response.
     */
    public function update(CreateResponseRequest $request, string $id): JsonResponse
    {
        try {
            $response = $this->responseService->updateResponse(
                $id,
                $request->validated()['response_text']
            );
            
            return response()->json([
                'message' => 'Response updated successfully',
                'data' => $response,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to update response',
            ], 500);
        }
    }

    /**
     * Delete a response.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->responseService->deleteResponse($id);
            
            return response()->json([
                'message' => 'Response deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to delete response',
            ], 500);
        }
    }
}
