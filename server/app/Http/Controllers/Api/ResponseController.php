<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateResponseRequest;
use App\Services\ResponseService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ResponseController extends Controller
{
    public function __construct(
        protected ResponseService $responseService
    ) {}

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
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Review not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Create Response Error', ['review_id' => $reviewId, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to create response',
            ], 500);
        }
    }

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
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Response not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Update Response Error', ['response_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to update response',
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->responseService->deleteResponse($id);
            
            return response()->json([
                'message' => 'Response deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Response not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Delete Response Error', ['response_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to delete response',
            ], 500);
        }
    }
}
