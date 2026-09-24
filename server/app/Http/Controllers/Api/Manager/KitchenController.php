<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\KitchenMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    protected KitchenMonitorService $kitchenService;

    public function __construct(KitchenMonitorService $kitchenService)
    {
        $this->kitchenService = $kitchenService;
    }

    public function orders(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->query('status'),
                'date' => $request->query('date'),
                'priority' => $request->query('priority'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
            ];

            $perPage = $request->query('per_page', 15);
            $orders = $this->kitchenService->getOrders($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Kitchen orders error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load kitchen orders',
            ], 500);
        }
    }

    public function metrics(): JsonResponse
    {
        try {
            $metrics = $this->kitchenService->getMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);
        } catch (\Exception $e) {
            \Log::error('Kitchen metrics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load kitchen metrics',
            ], 500);
        }
    }

    public function delayedOrders(): JsonResponse
    {
        try {
            $orders = $this->kitchenService->getDelayedOrders();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'count' => $orders->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Delayed orders error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load delayed orders',
            ], 500);
        }
    }

    public function performance(): JsonResponse
    {
        try {
            $performance = $this->kitchenService->getPerformance();

            return response()->json([
                'success' => true,
                'data' => $performance,
            ]);
        } catch (\Exception $e) {
            \Log::error('Kitchen performance error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load kitchen performance',
            ], 500);
        }
    }

    public function chefWorkload(): JsonResponse
    {
        try {
            $workload = $this->kitchenService->getChefWorkload();

            return response()->json([
                'success' => true,
                'data' => $workload,
            ]);
        } catch (\Exception $e) {
            \Log::error('Chef workload error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load chef workload',
            ], 500);
        }
    }

    public function topItems(Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 5);
            $items = $this->kitchenService->getTopPreparedItems($limit);

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('Top items error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load top items',
            ], 500);
        }
    }

    public function queueStatus(): JsonResponse
    {
        try {
            $status = $this->kitchenService->getQueueStatus();

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            \Log::error('Queue status error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load queue status',
            ], 500);
        }
    }
}
