<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Services\Waiter\WaiterDashboardService;
use Illuminate\Http\JsonResponse;

class WaiterDashboardController extends Controller
{
    protected WaiterDashboardService $dashboardService;

    public function __construct(WaiterDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    private function getWaiterId(): ?int
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                \Log::warning('❌ No authenticated user found');
                return null;
            }

            \Log::debug('🔵 [CONTROLLER] User authenticated:', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);

            // Load waiter relationship if not already loaded
            if (!$user->relationLoaded('waiter')) {
                \Log::debug('📥 Loading waiter relationship...');
                $user->load('waiter');
            }

            $waiter = $user->waiter;
            
            // If no waiter profile exists, try to find by user_id directly
            if (!$waiter) {
                \Log::warning('❌ No waiter relation found, searching by user_id', ['user_id' => $user->id]);
                $waiter = \App\Models\Waiter::where('user_id', $user->id)->first();
                
                if ($waiter) {
                    \Log::info('✅ Found waiter by direct user_id lookup', [
                        'user_id' => $user->id,
                        'waiter_id' => $waiter->id,
                    ]);
                } else {
                    \Log::error('❌ No waiter profile found for user', [
                        'user_id' => $user->id,
                        'user_role' => $user->role,
                    ]);
                    
                    // Log all waiters to debug
                    $allWaiters = \App\Models\Waiter::select('id', 'user_id', 'section')->get();
                    \Log::debug('📊 All waiters in system:', ['count' => $allWaiters->count(), 'waiters' => $allWaiters->toArray()]);
                    
                    return null;
                }
            }
            
            $waiterId = $waiter->id;
            
            \Log::debug('✅ [CONTROLLER] Waiter ID resolved:', [
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
                'waiter_section' => $waiter->section ?? 'N/A',
            ]);

            return $waiterId;
        } catch (\Throwable $e) {
            \Log::error('❌ Auth error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
    private function handleAction(callable $action, array $defaultData = []): JsonResponse
    {
        try {
            $waiterId = $this->getWaiterId();
            
            if (!$waiterId) {
                \Log::warning('Waiter dashboard requested without a linked waiter profile', [
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $defaultData,
                ], 200);
            }

            $result = $action($waiterId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Dashboard action error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $defaultData,
            ], 200);
        }
    }

    /**
     * Get complete dashboard statistics
     * GET /api/waiter/dashboard
     */
    public function getDashboard(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getDashboardStats($userId),
            [
                'today_stats' => [],
                'performance' => [],
                'recent_assignments' => [],
                'pending_count' => 0,
                'active_count' => 0,
            ]
        );
    }

    /**
     * Get today's statistics
     * GET /api/waiter/dashboard/today
     */
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

    /**
     * Get performance metrics
     * GET /api/waiter/dashboard/performance
     */
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

    /**
     * Get recent assignments
     * GET /api/waiter/dashboard/recent-assignments
     */
    public function getRecentAssignments(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getRecentAssignments($userId, request()->query('limit', 10)),
            []
        );
    }

    /**
     * Get all orders ready from kitchen
     * GET /api/waiter/dashboard/kitchen-ready-orders
     */
    public function getKitchenReadyOrders(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getAllKitchenReadyOrders(),
            []
        );
    }

    /**
     * Get orders ready for pickup
     * GET /api/waiter/dashboard/ready-pickup
     */
    public function getReadyForPickup(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getReadyForPickup($userId),
            []
        );
    }

    /**
     * Get pending orders awaiting acceptance (and ready from kitchen)
     * GET /api/waiter/dashboard/pending-pickup
     */
    public function getPendingPickupOrders(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getPendingPickupOrders($userId),
            []
        );
    }

    /**
     * Get orders on delivery
     * GET /api/waiter/dashboard/on-delivery
     */
    public function getOnDelivery(): JsonResponse
    {
        return $this->handleAction(
            fn($waiterId) => $this->dashboardService->getOnDelivery($waiterId),
            []
        );
    }

    /**
     * Get completed deliveries
     * GET /api/waiter/dashboard/completed
     */
    public function getCompletedDeliveries(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getCompletedDeliveries($userId, request()->query('limit', 10)),
            []
        );
    }

    /**
     * Get failed deliveries
     * GET /api/waiter/dashboard/failed
     */
    public function getFailedDeliveries(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getFailedDeliveries($userId, request()->query('limit', 10)),
            []
        );
    }

    /**
     * Get delivery timeline
     * GET /api/waiter/dashboard/timeline
     */
    public function getDeliveryTimeline(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getDeliveryTimeline($userId),
            []
        );
    }

    /**
     * Get weekly performance data
     * GET /api/waiter/dashboard/weekly-performance
     */
    public function getWeeklyPerformance(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getWeeklyPerformanceData($userId),
            []
        );
    }

    /**
     * Get monthly performance data
     * GET /api/waiter/dashboard/monthly-performance
     */
    public function getMonthlyPerformance(): JsonResponse
    {
        return $this->handleAction(
            fn($userId) => $this->dashboardService->getMonthlyPerformanceData($userId),
            []
        );
    }

    /**
     * Get performance comparison
     * GET /api/waiter/dashboard/performance-comparison
     */
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

    /**
     * Get quick stats for sidebar
     * GET /api/waiter/dashboard/quick-stats
     */
    public function getQuickStats(): JsonResponse
    {
        return $this->handleAction(
            function($userId) {
                \Log::info('🔵 [CONTROLLER] getQuickStats called', [
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
