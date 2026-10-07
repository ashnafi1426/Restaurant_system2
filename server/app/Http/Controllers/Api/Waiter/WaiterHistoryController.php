<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Services\Waiter\WaiterPerformanceService;
use App\Services\Waiter\WaiterContextResolver;
use App\Services\Waiter\WaiterAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaiterHistoryController extends Controller
{
    protected WaiterPerformanceService $performanceService;
    protected WaiterContextResolver $waiterContextResolver;
    protected WaiterAssignmentService $assignmentService;

    public function __construct(
        WaiterPerformanceService $performanceService,
        WaiterContextResolver $waiterContextResolver,
        WaiterAssignmentService $assignmentService
    ) {
        $this->performanceService = $performanceService;
        $this->waiterContextResolver = $waiterContextResolver;
        $this->assignmentService = $assignmentService;
    }

    public function getHistory(Request $request): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $filters = [
            'date' => $request->query('date'),
            'action' => $request->query('action'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_order' => $request->query('sort_order', 'desc'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $history = $this->assignmentService->getDeliveryHistory($waiterId, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'from' => $history->firstItem(),
                'to' => $history->lastItem(),
            ],
        ]);
    }

    public function getPerformanceHistory(Request $request): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json([
                'success' => false,
                'message' => 'start_date and end_date are required',
            ], 422);
        }

        $perPage = (int) $request->query('per_page', 30);
        $history = $this->performanceService->getPerformanceHistory(
            $waiterId,
            $startDate,
            $endDate,
            $perPage
        );

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'from' => $history->firstItem(),
                'to' => $history->lastItem(),
            ],
        ]);
    }

    public function getPerformanceReport(Request $request): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json([
                'success' => false,
                'message' => 'start_date and end_date are required',
            ], 422);
        }

        $report = $this->performanceService->generatePerformanceReport(
            $waiterId,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function getPerformanceTrend(Request $request): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $days = (int) $request->query('days', 7);

        $trend = $this->performanceService->getPerformanceTrend($waiterId, $days);

        return response()->json([
            'success' => true,
            'data' => $trend,
            'period_days' => $days,
        ]);
    }

    public function getDeliveryTimeDistribution(Request $request): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $date = $request->query('date');

        $distribution = $this->performanceService->getDeliveryTimeDistribution($waiterId, $date);

        return response()->json([
            'success' => true,
            'data' => $distribution,
        ]);
    }

    public function getMonthlyAverage(Request $request): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $month = $request->query('month');

        $average = $this->performanceService->getMonthlyAveragePerformance($waiterId, $month);

        return response()->json([
            'success' => true,
            'data' => $average,
        ]);
    }

    public function getStatistics(): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $stats = $this->performanceService->getStatistics($waiterId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function exportHistory(Request $request)
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $filters = [
            'date' => $request->query('date'),
            'action' => $request->query('action'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_order' => $request->query('sort_order', 'desc'),
        ];

        $history = $this->assignmentService->getDeliveryHistory($waiterId, $filters, 1000);

        $csv = "Date,Action,Order ID,Description\n";
        foreach ($history->items() as $log) {
            $csv .= "\"{$log->created_at}\",\"{$log->action}\",\"{$log->order_id}\",\"{$log->description}\"\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="delivery-history.csv"');
    }

    public function exportPerformanceReport(Request $request)
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json([
                'success' => false,
                'message' => 'start_date and end_date are required',
            ], 422);
        }

        $report = $this->performanceService->generatePerformanceReport(
            $waiterId,
            $startDate,
            $endDate
        );

        $csv = "Date,Deliveries,Failed,Rejected,Avg Delivery Time,Guest Rating,Rating\n";

        if (isset($report['daily_breakdown'])) {
            foreach ($report['daily_breakdown'] as $day) {
                $csv .= "\"{$day['date']}\",{$day['deliveries']},{$day['failed']},{$day['rejected']},{$day['average_delivery_time']},{$day['guest_rating']},{$day['rating']}\n";
            }
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="performance-report.csv"');
    }
}
