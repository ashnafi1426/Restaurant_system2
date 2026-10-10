<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckInRequest;
use App\Http\Resources\CheckInResource;
use App\Models\CheckIn;
use App\Services\CheckInService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class CheckInController extends Controller
{
    public function __construct(
        protected CheckInService $checkInService
    ) {}

    /**
     * List paginated check-in records for current hotel.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $hotelId = TenantContext::id();
        $perPage = $request->integer('per_page', 10);

        $checkIns = $this->checkInService->getCheckIns($request->all(), $perPage, $hotelId);

        return CheckInResource::collection($checkIns);
    }

    /**
     * Create a check-in record from a confirmed reservation.
     */
    public function store(StoreCheckInRequest $request): JsonResponse
    {
        $hotelId = TenantContext::id();

        try {
            $checkIn = $this->checkInService->checkIn($request->validated('reservation_id'), $hotelId);

            return response()->json([
                'success' => true,
                'message' => 'Guest checked in successfully.',
                'data'    => new CheckInResource($checkIn),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    /**
     * Show single check-in record.
     */
    public function show(CheckIn $checkIn): CheckInResource
    {
        $checkIn->loadMissing([
            'guest',
            'room.roomType',
            'reservation',
        ]);

        return new CheckInResource($checkIn);
    }

    /**
     * Perform guest checkout for an active check-in record.
     */
    public function checkout(CheckIn $checkIn): JsonResponse
    {
        $hotelId = TenantContext::id();

        try {
            $updated = $this->checkInService->checkOut($checkIn, $hotelId);

            return response()->json([
                'success' => true,
                'message' => 'Guest checked out successfully.',
                'data'    => new CheckInResource($updated),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    /**
     * Delete a check-in record.
     */
    public function destroy(CheckIn $checkIn): JsonResponse
    {
        $this->checkInService->deleteCheckIn($checkIn);

        return response()->json([
            'success' => true,
            'message' => 'Check-in deleted successfully.',
        ]);
    }

    /**
     * Check-in statistics scoped to the hotel.
     */
    public function statistics(): JsonResponse
    {
        $hotelId = TenantContext::id();
        $statistics = $this->checkInService->getStatistics($hotelId);

        return response()->json($statistics);
    }
}

