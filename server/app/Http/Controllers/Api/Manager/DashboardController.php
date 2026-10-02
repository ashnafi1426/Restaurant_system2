<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardStatsResource;
use App\Services\Manager\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    private function handleAction(callable $action, array $defaultData = []): JsonResponse
    {
        try {
            $user = auth()->user();
            
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
        } catch (Throwable $e) {
            Log::error('Manager dashboard action error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => true,
                'data' => $defaultData,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        }
    }

    public function index(): JsonResponse
    {
        return $this->handleAction(
            fn() => new DashboardStatsResource($this->dashboardService->getDashboardStats()),
            []
        );
    }

    public function statistics(): JsonResponse
    {
        return $this->handleAction(
            fn() => new DashboardStatsResource($this->dashboardService->getDashboardStats()),
            []
        );
    }

    public function dailyTrends(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        
        return $this->handleAction(
            fn() => $this->dashboardService->getDailyStats($days),
            []
        );
    }

    public function topSellingItems(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 5);
        
        return $this->handleAction(
            fn() => $this->dashboardService->getTopSellingItems($limit),
            []
        );
    }

    public function performanceSummary(): JsonResponse
    {
        return $this->handleAction(
            fn() => $this->dashboardService->getPerformanceSummary(),
            []
        );
    }
}
