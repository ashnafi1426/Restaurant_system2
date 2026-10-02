<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function __construct(
        protected ManagerDashboardService $dashboardService
    ) {}

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
        } catch (Exception $e) {
            Log::error('Manager analytics error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
