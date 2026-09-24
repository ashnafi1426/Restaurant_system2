<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\WaiterTableAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WaiterTableAssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(WaiterTableAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

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

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'waiter_id' => 'nullable|integer|exists:waiters,id',
            'table_id' => 'nullable|uuid|exists:restaurant_tables,id',
            'shift_id' => 'nullable|uuid|exists:shifts,id',
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
                $request->only(['waiter_id', 'table_id', 'shift_id', 'priority', 'status'])
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
