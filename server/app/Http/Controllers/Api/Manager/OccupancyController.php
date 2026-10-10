<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OccupancyController extends Controller
{
    public function __construct(
        protected ManagerDashboardService $dashboardService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        try {
            $occupancySummary = $this->dashboardService->occupancySummary();

            return response()->json([
                'success' => true,
                'data' => $occupancySummary,
            ]);
        } catch (Exception $e) {
            Log::error('Manager occupancy summary error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load occupancy summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function chart(Request $request): JsonResponse
    {
        try {
            $occupancyChart = $this->dashboardService->occupancyChart();

            return response()->json([
                'success' => true,
                'data' => $occupancyChart,
            ]);
        } catch (Exception $e) {
            Log::error('Manager occupancy chart error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load occupancy chart: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reservations(Request $request): JsonResponse
    {
        try {
            $reservationSummary = $this->dashboardService->reservationSummary();

            return response()->json([
                'success' => true,
                'data' => $reservationSummary,
            ]);
        } catch (Exception $e) {
            Log::error('Manager occupancy reservations error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load reservation summary: ' . $e->getMessage(),
            ], 500);
        }
    }
}

