<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RevenueController extends Controller
{
    public function __construct(
        protected ManagerDashboardService $dashboardService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        try {
            $revenueSummary = $this->dashboardService->revenueSummary();

            return response()->json([
                'success' => true,
                'data' => $revenueSummary,
            ]);
        } catch (Exception $e) {
            Log::error('Manager revenue summary error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load revenue summary: ' . $e->getMessage(),
            ], 500);
        }
    }

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
        } catch (Exception $e) {
            Log::error('Manager revenue chart error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load revenue chart: ' . $e->getMessage(),
            ], 500);
        }
    }
}

