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
        $this->assignmentService->ensureTableExists();
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

    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: app(\App\Services\TenantContext::class)->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if ($hotelId) {
            app(\App\Services\TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function store(Request $request): JsonResponse
    {
        $hotelId = $this->getHotelId();

        // Normalize payload: support either { table_id, waiter_ids: [], status } OR { assignments: [] }
        $assignments = [];

        if ($request->has('table_id') && ($request->has('waiter_ids') || $request->has('waiter_id'))) {
            $tableId = $request->input('table_id');
            $waiterIds = $request->has('waiter_ids') 
                ? (array) $request->input('waiter_ids') 
                : [$request->input('waiter_id')];

            $status = $request->input('status');
            if ($status === null && $request->has('is_active')) {
                $status = $request->boolean('is_active') ? 'active' : 'inactive';
            }
            $status = $status ?? 'active';

            foreach ($waiterIds as $wId) {
                $assignments[] = [
                    'table_id' => $tableId,
                    'waiter_id' => $wId,
                    'status' => $status,
                    'shift_id' => $request->input('shift_id', null),
                    'assignment_date' => $request->input('assignment_date', today()->toDateString()),
                    'priority' => $request->input('priority', 'primary'),
                ];
            }
        } elseif ($request->has('assignments') && is_array($request->input('assignments'))) {
            foreach ($request->input('assignments') as $item) {
                $status = $item['status'] ?? null;
                if ($status === null && isset($item['is_active'])) {
                    $status = filter_var($item['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'active' : 'inactive';
                }
                $status = $status ?? 'active';

                $assignments[] = [
                    'table_id' => $item['table_id'] ?? null,
                    'waiter_id' => $item['waiter_id'] ?? null,
                    'status' => $status,
                    'shift_id' => $item['shift_id'] ?? null,
                    'assignment_date' => $item['assignment_date'] ?? today()->toDateString(),
                    'priority' => $item['priority'] ?? 'primary',
                ];
            }
        }

        if (empty($assignments)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: Restaurant Table and at least one Waiter are required.',
                'errors' => [
                    'table_id' => ['Restaurant table is required.'],
                    'waiter_ids' => ['At least one waiter must be assigned.']
                ]
            ], 422);
        }

        // Validate table and each waiter
        $tableIds = array_unique(array_filter(array_column($assignments, 'table_id')));
        $waiterIds = array_unique(array_filter(array_column($assignments, 'waiter_id')));

        if (empty($tableIds)) {
            return response()->json([
                'success' => false,
                'message' => 'A valid Restaurant Table is required.',
            ], 422);
        }

        if (empty($waiterIds)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one active waiter must be selected.',
            ], 422);
        }

        // Check tables exist and belong to hotel
        $tables = \App\Models\RestaurantTable::withoutTenant()->whereIn('id', $tableIds)->get()->keyBy('id');
        foreach ($tableIds as $tId) {
            $tbl = $tables->get($tId);
            if (!$tbl) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected restaurant table was not found.',
                ], 422);
            }
            if ($hotelId && $tbl->hotel_id && $tbl->hotel_id !== $hotelId) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected table belongs to another hotel.',
                ], 422);
            }
        }

        // Check waiters exist, are active, and belong to current hotel
        $waiters = \App\Models\Waiter::with('user')->whereIn('id', $waiterIds)->get()->keyBy('id');
        foreach ($waiterIds as $wId) {
            $waiter = $waiters->get($wId);
            if (!$waiter) {
                return response()->json([
                    'success' => false,
                    'message' => "Waiter #{$wId} was not found.",
                ], 422);
            }
            $waiterName = $waiter->user?->name ?? $waiter->name ?? "Waiter #{$wId}";
            if (strtolower($waiter->status ?? 'active') !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => "Waiter {$waiterName} is inactive. Only active waiters from the current hotel can be assigned.",
                ], 422);
            }
            if ($hotelId && $waiter->hotel_id && $waiter->hotel_id !== $hotelId) {
                return response()->json([
                    'success' => false,
                    'message' => "Waiter {$waiterName} belongs to another hotel. Only active waiters from the current hotel can be assigned.",
                ], 422);
            }
        }

        try {
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
                'message' => 'Failed to create assignments: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $hotelId = $this->getHotelId();

        $validator = Validator::make($request->all(), [
            'waiter_id' => 'nullable|integer|exists:waiters,id',
            'table_id' => 'nullable|uuid|exists:restaurant_tables,id',
            'status' => 'nullable|in:active,inactive,completed',
            'is_active' => 'nullable|boolean',
            'shift_id' => 'nullable',
            'priority' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // If waiter_id is provided, verify waiter is active and belongs to hotel
        if ($request->filled('waiter_id')) {
            $waiter = \App\Models\Waiter::with('user')->find($request->input('waiter_id'));
            if ($waiter) {
                $waiterName = $waiter->user?->name ?? "Waiter #{$waiter->id}";
                if (strtolower($waiter->status ?? 'active') !== 'active') {
                    return response()->json([
                        'success' => false,
                        'message' => "Waiter {$waiterName} is inactive. Only active waiters from the current hotel can be assigned.",
                    ], 422);
                }
                if ($hotelId && $waiter->hotel_id && $waiter->hotel_id !== $hotelId) {
                    return response()->json([
                        'success' => false,
                        'message' => "Waiter {$waiterName} belongs to another hotel.",
                    ], 422);
                }
            }
        }

        try {
            $updateData = $request->only(['waiter_id', 'table_id', 'shift_id', 'priority', 'status']);
            if (!$request->has('status') && $request->has('is_active')) {
                $updateData['status'] = $request->boolean('is_active') ? 'active' : 'inactive';
            }

            $assignment = $this->assignmentService->updateAssignment(
                $id,
                $updateData
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
                'message' => 'Failed to update assignment: ' . $e->getMessage(),
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
