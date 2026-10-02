<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffController extends Controller
{
    public function __construct(
        protected ManagerDashboardService $dashboardService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $staff = $this->dashboardService->getStaff();
            
            return response()->json([
                'success' => true,
                'data' => $staff,
            ]);
        } catch (Exception $e) {
            Log::error('Manager staff error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load staff data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
