<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OccupancyController extends Controller
{
    protected ManagerDashboardService $dashboardService;

    public function __construct(ManagerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function summary(Request $request): JsonResponse
    {
        try {
            $occupancySummary = $this->dashboardService->occupancySummary();
            
            return response()->json([
                'success' => true,
                'data' => $occupancySummary,
            ]);
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load reservation summary: ' . $e->getMessage(),
            ], 500);
        }
    }
}
