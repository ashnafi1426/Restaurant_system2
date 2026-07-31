<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Waiter\AcceptAssignmentRequest;
use App\Http\Requests\Waiter\RejectAssignmentRequest;
use App\Http\Requests\Waiter\DeliverOrderRequest;
use App\Http\Requests\Waiter\FailedDeliveryRequest;
use App\Http\Resources\Waiter\WaiterAssignmentResource;
use App\Models\DeliveryTask;
use App\Services\Waiter\WaiterContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaiterAssignmentController extends Controller
{
    protected \App\Services\Waiter\WaiterAssignmentService $assignmentService;
    protected WaiterContextResolver $waiterContextResolver;

    public function __construct(
        \App\Services\Waiter\WaiterAssignmentService $assignmentService,
        WaiterContextResolver $waiterContextResolver
    )
    {
        $this->assignmentService = $assignmentService;
        $this->waiterContextResolver = $waiterContextResolver;
    }

    /**
     * Get all assignments for waiter
     * GET /api/waiter/assignments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());

            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }

            \Log::info('🔵 [API] WaiterAssignmentController::index called', [
                'waiter_id' => $waiterId,
                'query_params' => $request->all(),
            ]);
            
            $filters = [
                'status' => $request->query('status'),
                'date' => $request->query('date'),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'assigned_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
            ];

            $perPage = $request->query('per_page', 15);
            $assignments = $this->assignmentService->getWaiterAssignments($waiterId, $filters, $perPage);
            
            \Log::info(' [API] Assignments retrieved', [
                'waiter_id' => $waiterId,
                'total' => $assignments->total(),
                'per_page' => $assignments->perPage(),
            ]);

            return response()->json([
                'success' => true,
                'data' => WaiterAssignmentResource::collection($assignments),
                'pagination' => [
                    'total' => $assignments->total(),
                    'per_page' => $assignments->perPage(),
                    'current_page' => $assignments->currentPage(),
                    'last_page' => $assignments->lastPage(),
                    'from' => $assignments->firstItem(),
                    'to' => $assignments->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ [API] Assignment fetch error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single assignment
     * GET /api/waiter/assignments/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $assignment = $this->assignmentService->getAssignment($id);

            // Verify ownership
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());

            if (!$waiterId || (int) $assignment->waiter_id !== (int) $waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $assignment,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getPending(): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            $assignments = $this->assignmentService->getPendingAssignments($waiterId);

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => count($assignments),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active assignments
     * GET /api/waiter/assignments/active
     */
    public function getActive(): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            $assignments = $this->assignmentService->getActiveAssignments($waiterId);

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => count($assignments),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's assignments
     * GET /api/waiter/assignments/today
     */
    public function getToday(): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            $assignments = $this->assignmentService->getTodayAssignments($waiterId);

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => count($assignments),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch today assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Accept assignment
     * PATCH /api/waiter/assignments/{id}/accept
     */
    public function accept(AcceptAssignmentRequest $request, $id): JsonResponse
    {
        try {
            \Log::info('🔵 [ENDPOINT] accept called', ['task_id' => $id]);
            
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            
            \Log::info('📋 [ENDPOINT] Waiter ID resolved in accept', [
                'task_id' => $id,
                'waiter_id' => $waiterId,
            ]);
            
            if (!$waiterId) {
                \Log::error('❌ [ENDPOINT] Waiter profile not linked for accept');
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            
            $assignment = $this->assignmentService->acceptAssignment($id, $waiterId);

            \Log::info('✅ [ENDPOINT] accept succeeded', [
                'task_id' => $assignment->id,
                'status' => $assignment->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment accepted successfully',
                'data' => $assignment,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('⚠️ [ENDPOINT] Assignment not found for accept', ['task_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('❌ [ENDPOINT] Failed to accept assignment', [
                'task_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject assignment
     * PATCH /api/waiter/assignments/{id}/reject
     */
    public function reject(RejectAssignmentRequest $request, $id): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            $reason = $request->validated()['reason'] ?? null;
            $assignment = $this->assignmentService->rejectAssignment($id, $waiterId, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Assignment rejected successfully',
                'data' => $assignment,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pickup order
     * PATCH /api/waiter/assignments/{id}/pickup
     */
    public function pickup($id): JsonResponse
    {
        try {
            \Log::info('🔵 [CONTROLLER] Pickup endpoint called', ['id' => $id]);
            
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                \Log::error('❌ [CONTROLLER] Waiter profile not linked');
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            
            \Log::info('✅ [CONTROLLER] Waiter ID resolved', ['waiter_id' => $waiterId]);
            
            $assignment = $this->assignmentService->pickupOrder($id, $waiterId);
            
            \Log::info('✅ [CONTROLLER] Order picked up successfully', ['id' => $id, 'assignment' => $assignment]);

            return response()->json([
                'success' => true,
                'message' => 'Order picked up successfully',
                'data' => $assignment,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('❌ [CONTROLLER] Assignment not found', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('❌ [CONTROLLER] Pickup error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to pickup order: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start delivery (mark as on_delivery)
     * PATCH /api/waiter/assignments/{id}/start-delivery
     */
    public function startDelivery($id): JsonResponse
    {
        try {
            \Log::info('🔵 [ENDPOINT] startDelivery called', ['task_id' => $id]);
            
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            
            \Log::info('📋 [ENDPOINT] Waiter ID resolved in startDelivery', [
                'task_id' => $id,
                'waiter_id' => $waiterId,
            ]);
            
            if (!$waiterId) {
                \Log::error('❌ [ENDPOINT] Waiter profile not linked');
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            
            $assignment = $this->assignmentService->startDelivery($id, $waiterId);

            \Log::info('✅ [ENDPOINT] startDelivery succeeded', [
                'task_id' => $assignment->id,
                'status' => $assignment->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery started successfully',
                'data' => $assignment,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('⚠️ [ENDPOINT] Assignment not found', ['task_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('❌ [ENDPOINT] Failed to start delivery', [
                'task_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to start delivery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deliver order
     * PATCH /api/waiter/assignments/{id}/deliver
     */
    public function deliver(DeliverOrderRequest $request, $id): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            $remarks = $request->validated()['remarks'] ?? null;
            $assignment = $this->assignmentService->deliverOrder($id, $waiterId, $remarks);

            return response()->json([
                'success' => true,
                'message' => 'Order delivered successfully',
                'data' => $assignment,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deliver order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark delivery as failed
     * PATCH /api/waiter/assignments/{id}/failed
     */
    public function failed(FailedDeliveryRequest $request, $id): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            $validated = $request->validated();
            $reason = $validated['reason'];
            $remarks = $validated['remarks'] ?? null;

            $assignment = $this->assignmentService->failDelivery($id, $waiterId, $reason, $remarks);

            return response()->json([
                'success' => true,
                'message' => 'Delivery marked as failed',
                'data' => $assignment,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark delivery as failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
