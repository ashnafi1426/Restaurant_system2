<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantSectionRequest;
use App\Http\Resources\RestaurantSectionResource;
use App\Models\RestaurantSection;
use App\Services\RestaurantSectionService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestaurantSectionController extends Controller
{
    public function __construct(
        protected RestaurantSectionService $sectionService,
        protected TenantContext $tenantContext
    ) {}

    protected function getHotelId(): ?string
    {
        return $this->tenantContext->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null)
            ?: request()->header('X-Hotel-ID');
    }

    /**
     * Display a listing of restaurant sections.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $activeOnly = $request->boolean('active_only', false);

            $sections = $this->sectionService->getSections($hotelId, $activeOnly);

            return response()->json([
                'success' => true,
                'data'    => RestaurantSectionResource::collection($sections),
            ], 200);

        } catch (Throwable $e) {
            Log::error('Failed to fetch restaurant sections', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant sections',
            ], 500);
        }
    }

    /**
     * Display the specified section.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $section = $this->sectionService->getSection($id, $hotelId);

            return response()->json([
                'success' => true,
                'data'    => new RestaurantSectionResource($section),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant section not found',
            ], 404);

        } catch (Throwable $e) {
            Log::error('Failed to fetch restaurant section', ['section_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant section',
            ], 500);
        }
    }

    /**
     * Store a newly created section.
     */
    public function store(StoreRestaurantSectionRequest $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $section = $this->sectionService->createSection($request->validated(), $hotelId);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant section created successfully',
                'data'    => new RestaurantSectionResource($section),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create restaurant section', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create restaurant section: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified section.
     */
    public function update(StoreRestaurantSectionRequest $request, string $id): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $section = $this->sectionService->getSection($id, $hotelId);
            $updated = $this->sectionService->updateSection($section, $request->validated(), $hotelId);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant section updated successfully',
                'data'    => new RestaurantSectionResource($updated),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant section not found',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update restaurant section', ['section_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update restaurant section: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified section.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $section = $this->sectionService->getSection($id, $hotelId);
            $this->sectionService->deleteSection($section);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant section deleted successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant section not found',
            ], 404);

        } catch (Throwable $e) {
            Log::error('Failed to delete restaurant section', ['section_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete restaurant section',
            ], 500);
        }
    }
}
