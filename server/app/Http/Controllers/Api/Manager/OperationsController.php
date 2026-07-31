<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager Operations Controller
 * 
 * Handles restaurant orders, room service, laundry, and housekeeping
 */
class OperationsController extends Controller
{
    protected ManagerDashboardService $dashboardService;

    public function __construct(ManagerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get restaurant orders
     */
    public function orders(Request $request): JsonResponse
    {
        try {
            $orders = $this->dashboardService->getRecentOrders();
            
            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get room service deliveries
     */
    public function deliveries(Request $request): JsonResponse
    {
        try {
            $deliveries = $this->dashboardService->getDeliveries();
            
            return response()->json([
                'success' => true,
                'data' => $deliveries,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load deliveries: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get housekeeping tasks
     */
    public function housekeeping(Request $request): JsonResponse
    {
        try {
            $housekeeping = $this->dashboardService->getHousekeeping();
            
            return response()->json([
                'success' => true,
                'data' => $housekeeping,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load housekeeping data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get laundry requests
     */
    public function laundry(Request $request): JsonResponse
    {
        try {
            $laundry = $this->dashboardService->getLaundry();
            
            return response()->json([
                'success' => true,
                'data' => $laundry,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load laundry data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
