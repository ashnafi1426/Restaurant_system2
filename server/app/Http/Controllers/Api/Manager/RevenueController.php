<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager Revenue Controller
 * 
 * Handles all revenue tracking and analysis
 */
class RevenueController extends Controller
{
    protected ManagerDashboardService $dashboardService;

    public function __construct(ManagerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get revenue summary
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $revenueSummary = $this->dashboardService->revenueSummary();
            
            return response()->json([
                'success' => true,
                'data' => $revenueSummary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load revenue summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get revenue chart data
     */
    public function chart(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'monthly');
            $revenueChart = $this->dashboardService->revenueChart($period);
            
            return response()->json([
                'success' => true,
                'data' => $revenueChart,
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load revenue chart: ' . $e->getMessage(),
            ], 500);
        }
    }
}
