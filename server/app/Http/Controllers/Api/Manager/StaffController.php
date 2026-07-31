<?php

namespace App\Http\Controllers\Api\Manager;
use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class StaffController extends Controller
{
    protected ManagerDashboardService $dashboardService;

    public function __construct(ManagerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get staff overview
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $staff = $this->dashboardService->getStaff();
            
            return response()->json([
                'success' => true,
                'data' => $staff,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load staff data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
