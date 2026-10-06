<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Services\Waiter\WaiterDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TenantContext;
use App\Services\Waiter\WaiterContextResolver;

class WaiterDashboardController extends Controller
{
    protected WaiterDashboardService $dashboardService;
    public function __construct(WaiterDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    private function resolveTenant(Request $request): ?string
    {
        $hotelId = $request->input('hotel_id') 
            ?: $request->query('hotel_id')
            ?: $request->header('X-Hotel-ID') 
            ?: app(TenantContext::class)->getHotelId();

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    private function getWaiterId()
    {
        try {
            $user = auth()->user();
            if (!$user) return null;
            return app(WaiterContextResolver::class)->resolveWaiterId($user);
        } catch (\Throwable $e) {
            \Log::error(' Auth error in getWaiterId: ' . $e->getMessage());
            return null;
        }
    }

    private function handleAction(callable $action, array $defaultData = []): JsonResponse
    {
        try {
            $request = request();
            $this->resolveTenant($request);
            $waiterId = $this->getWaiterId();

            $result = $action($waiterId);

            return response()->json([
                'success' => true,
                'data' => $result ?? $defaultData,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Dashboard action error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'data' => $defaultData,
            ], 500);
        }
    }

    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->resolveTenant($request);
            $waiterId = $this->getWaiterId();

            $result = $this->dashboardService->getDashboardStats($waiterId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Dashboard load error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
                'data' => [
                    'today_stats' => $this->dashboardService->getDefaultTodayStats(),
                    'performance' => $this->dashboardService->getDefaultPerformanceMetrics(),
                    'recent_assignments' => [],
                    'pending_count' => 0,
                    'active_count' => 0,
                ],
            ], 500);
        }
    }

    public function getTodayStats(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getTodayStats($userId),
            [
                'total_assignments' => 0,
                'completed_deliveries' => 0,
                'failed_deliveries' => 0,
                'rejected_assignments' => 0,
                'pending_assignments' => 0,
                'active_assignments' => 0,
                'average_delivery_time' => 0,
                'completion_rate' => 0,
            ]
        );
    }

    public function getPerformance(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getPerformanceMetrics($userId),
            [
                'today' => ['deliveries' => 0, 'failed' => 0, 'average_delivery_time' => 0, 'rating' => 0, 'guest_rating' => 0],
                'week' => ['deliveries' => 0, 'failed' => 0, 'average_delivery_time' => 0, 'rating' => 0, 'guest_rating' => 0],
                'month' => ['deliveries' => 0, 'failed' => 0, 'average_delivery_time' => 0, 'rating' => 0, 'guest_rating' => 0],
            ]
        );
    }

    public function getRecentAssignments(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getRecentAssignments($userId, request()->query('limit', 10)),
            []
        );
    }

    public function getKitchenReadyOrders(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getAllKitchenReadyOrders($userId, request()->query('limit', 50)),
        );
    }

    public function getReadyForPickup(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getReadyForPickup($userId, request()->query('limit', 50)),
        );
    }

    public function getPendingPickupOrders(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getPendingPickupOrders($userId),
            
        );
    }

    public function getOnDelivery(): JsonResponse
    {
        return $this->handleAction(
            fn($waiterId) => $this->dashboardService->getOnDelivery($waiterId, request()->query('limit', 50)),
            
        );
    }

    public function getCompletedDeliveries(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getCompletedDeliveries($userId, request()->query('limit', 10)),
            
        );
    }

    public function getFailedDeliveries(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getFailedDeliveries($userId, request()->query('limit', 10)),
            
        );
    }

    public function getDeliveryTimeline(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getDeliveryTimeline($userId),
            
        );
    }

    public function getWeeklyPerformance(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getWeeklyPerformanceData($userId),
            
        );
    }

    public function getMonthlyPerformance(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getMonthlyPerformanceData($userId),
            
        );
    }

    public function getPerformanceComparison(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getPerformanceComparison($userId),
            [
                'this_week' => ['deliveries' => 0, 'failed' => 0, 'average_delivery_time' => 0, 'rating' => 0],
                'last_week' => ['deliveries' => 0, 'failed' => 0, 'average_delivery_time' => 0, 'rating' => 0],
                'growth' => ['deliveries' => 0],
            ]
        );
    }

    public function getQuickStats(): JsonResponse
    {
        return $this->handleAction(
            function($userId) {
                \Log::info(' [CONTROLLER] getQuickStats called', [
                    'user_id' => $userId,
                    'user_type' => class_basename(auth()->user()),
                ]);
                
                $stats = $this->dashboardService->getQuickStats($userId);
                
                \Log::info(' [CONTROLLER] getQuickStats result', [
                    'user_id' => $userId,
                    'stats' => $stats,
                ]);
                
                return $stats;
            },
            [
                'pending' => 0,
                'active' => 0,
                'completed' => 0,
                'failed' => 0,
            ]
        );
    }
}
