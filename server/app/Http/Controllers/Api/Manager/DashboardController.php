<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardStatsResource;
use App\Services\Manager\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    private function getUser()
    {
        try {
            return auth()->user();
        } catch (\Throwable $e) {
            Log::error('Auth error:', ['message' => $e->getMessage()]);
            return null;
        }
    }
    private function handleAction(callable $action, array $defaultData = []): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'data' => $defaultData,
                    'timestamp' => now()->toIso8601String(),
                ], 200);
            }

            $result = $action();

            return response()->json([
                'success' => true,
                'data' => $result,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Dashboard action error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $defaultData,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        }
    }

    /**
     * Get complete dashboard data
     */
    public function index(): JsonResponse
    {
        return $this->handleAction(
            fn() => new DashboardStatsResource($this->dashboardService->getDashboardStats()),
            []
        );
    }

    /**
     * Get dashboard statistics only
     */
    public function statistics(): JsonResponse
    {
        return $this->handleAction(
            fn() => new DashboardStatsResource($this->dashboardService->getDashboardStats()),
            []
        );
    }

    /**
     * Get daily trend data for charts
     */
    public function dailyTrends(Request $request): JsonResponse
    {
        $days = $request->query('days', 7);
        
        return $this->handleAction(
            fn() => $this->dashboardService->getDailyStats($days),
            []
        );
    }

    /**
     * Get top selling items
     */
    public function topSellingItems(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 5);
        
        return $this->handleAction(
            fn() => $this->dashboardService->getTopSellingItems($limit),
            []
        );
    }

    /**
     * Get performance summary
     */
    public function performanceSummary(): JsonResponse
    {
        return $this->handleAction(
            fn() => $this->dashboardService->getPerformanceSummary(),
            []
        );
    }
}
