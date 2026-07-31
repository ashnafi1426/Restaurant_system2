<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager Analytics Controller
 * 
 * Handles analytics and reports data
 */
class AnalyticsController extends Controller
{
    protected ManagerDashboardService $dashboardService;

    public function __construct(ManagerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get analytics dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $analytics = [
                'occupancy' => $this->dashboardService->occupancySummary(),
                'revenue' => $this->dashboardService->revenueSummary(),
                'statistics' => $this->dashboardService->statistics(),
                'reservations' => $this->dashboardService->reservationSummary(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
