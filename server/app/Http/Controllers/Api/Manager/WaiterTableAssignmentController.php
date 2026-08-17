<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\WaiterTableAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Controller for managing waiter-to-table assignments
 * Handles API endpoints for walk-in customer service management
 */
class WaiterTableAssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(WaiterTableAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Get all table assignments with filters
     * 
     * GET /api/manager/table-assignments
     * 
     * Query params:
     * - date: YYYY-MM-DD
     * - waiter_id: int
     * - table_id: uuid
     * - shift_id: uuid
     * - status: active/inactive/completed
     * - priority: primary/secondary/backup
     * - per_page: int (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date',
                'waiter_id',
                'table_id',
                'shift_id',
                'status',
                'priority',
                'per_page'
            ]);

            $assignments = $this->assignmentService->getAssignments($filters);

            return response()->json([
                'success' => true,
                'data' => $assignments->items(),
                'pagination' => [
                    'current_page' => $assignments->currentPage(),
                    'per_page' => $assignments->perPage(),
                    'total' => $assignments->total(),
                    'last_page' => $assignments->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch table assignments', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's active assignments
     * 
     * GET /api/manager/table-assignments/today
     */
    public function today(): JsonResponse
    {
        try {
            $assignments = $this->assignmentService->getTodayAssignments();

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => $assignments->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch today assignments', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch today assignments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign waiters to tables (batch)
     * 
     * POST /api/manager/table-assignments
     * 
     * Body:
     * {
     *   "assignments": [
     *     {
     *       "waiter_id": 123,
     *       "table_id": "uuid-table-5",
     *       "shift_id": "uuid-morning-shift",
     *       "assignment_date": "2026-08-17",
     *       "priority": "primary"
     *     }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignments' => 'required|array|min:1',
            'assignments.*.waiter_id' => 'required|integer|exists:waiters,id',
            'assignments.*.table_id' => 'required|uuid|exists:restaurant_tables,id',
            'assignments.*.shift_id' => 'required|uuid|exists:hotel_shifts,id',
            'assignments.*.assignment_date' => 'required|date|after_or_equal:today',
            'assignments.*.priority' => 'nullable|in:primary,secondary,backup',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignments = $request->input('assignments');
            $assignedBy = auth()->id();

            $result = $this->assignmentService->assignWaitersToTables($assignments, $assignedBy);

            $statusCode = $result['total_created'] > 0 ? 201 : 400;

            return response()->json([
                'success' => $result['success'],
                'message' => $result['total_created'] > 0 
                    ? "Successfully assigned {$result['total_created']} waiter(s) to table(s)"
                    : 'No assignments were created',
                'data' => $result['created'],
                'errors' => $result['errors'],
                'summary' => [
                    'total_created' => $result['total_created'],
                    'total_errors' => $result['total_errors'],
                ]
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error('Failed to create table assignments', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update assignment
     * 
     * PATCH /api/manager/table-assignments/{id}
     * 
     * Body:
     * {
     *   "priority": "secondary",
     *   "status": "inactive"
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'priority' => 'nullable|in:primary,secondary,backup',
            'status' => 'nullable|in:active,inactive,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignment = $this->assignmentService->updateAssignment(
                $id,
                $request->only(['priority', 'status'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => $assignment
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to update table assignment', [
                'assignment_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete assignment
     * 
     * DELETE /api/manager/table-assignments/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->assignmentService->deleteAssignment($id);

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete table assignment', [
                'assignment_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assignment statistics
     * 
     * GET /api/manager/table-assignments/stats?date=2026-08-17
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $date = $request->query('date');
            $stats = $this->assignmentService->getAssignmentStats($date);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch assignment stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assigned waiter for a table
     * 
     * GET /api/manager/table-assignments/table/{tableId}/assigned-waiter
     */
    public function getAssignedWaiter(string $tableId): JsonResponse
    {
        try {
            $assignment = $this->assignmentService->getAssignedWaiterForTable($tableId);

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No waiter assigned to this table at current time'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get assigned waiter for table', [
                'table_id' => $tableId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get assigned waiter',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tables assigned to a waiter
     * 
     * GET /api/manager/table-assignments/waiter/{waiterId}/tables?date=2026-08-17
     */
    public function getWaiterTables(Request $request, int $waiterId): JsonResponse
    {
        try {
            $date = $request->query('date');
            $tables = $this->assignmentService->getWaiterTables($waiterId, $date);

            return response()->json([
                'success' => true,
                'data' => $tables,
                'count' => $tables->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get waiter tables', [
                'waiter_id' => $waiterId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get waiter tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
