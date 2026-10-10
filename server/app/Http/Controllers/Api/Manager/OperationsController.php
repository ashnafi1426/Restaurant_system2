<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OperationsController extends Controller
{
    public function __construct(
        protected ManagerDashboardService $dashboardService
    ) {}

    public function orders(Request $request): JsonResponse
    {
        try {
            $orders = $this->dashboardService->getRecentOrders();

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (Exception $e) {
            Log::error('Manager operations orders error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deliveries(Request $request): JsonResponse
    {
        try {
            $deliveries = $this->dashboardService->getDeliveries();

            return response()->json([
                'success' => true,
                'data' => $deliveries,
            ]);
        } catch (Exception $e) {
            Log::error('Manager operations deliveries error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load deliveries: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function housekeeping(Request $request): JsonResponse
    {
        try {
            $housekeeping = $this->dashboardService->getHousekeeping();

            return response()->json([
                'success' => true,
                'data' => $housekeeping,
            ]);
        } catch (Exception $e) {
            Log::error('Manager operations housekeeping error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load housekeeping data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function laundry(Request $request): JsonResponse
    {
        try {
            $laundry = $this->dashboardService->getLaundry();

            return response()->json([
                'success' => true,
                'data' => $laundry,
            ]);
        } catch (Exception $e) {
            Log::error('Manager operations laundry error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load laundry data: ' . $e->getMessage(),
            ], 500);
        }
    }
}

