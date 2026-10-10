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
use App\Services\Waiter\WaiterAssignmentService;
use App\Services\TenantContext;

class WaiterAssignmentController extends Controller
{
    protected WaiterAssignmentService $assignmentService;
    protected WaiterContextResolver $waiterContextResolver;

    public function __construct(
        WaiterAssignmentService $assignmentService,
        WaiterContextResolver $waiterContextResolver
    )
    {
        $this->assignmentService = $assignmentService;
        $this->waiterContextResolver = $waiterContextResolver;
    }

    private function resolveTenant(Request $request): ?string
    {
        $hotelId = $request->input('hotel_id') 
            ?: $request->query('hotel_id')
            ?: $request->header('X-Hotel-ID') 
            ?: app(TenantContext::class)->getHotelId();

        if ($hotelId) {
            app(TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function index(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());

        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }

        $filters = [
            'status' => $request->query('status'),
            'date' => $request->query('date'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'assigned_at'),
            'sort_order' => $request->query('sort_order', 'desc'),
        ];
        $perPage = (int) $request->query('per_page', 15);
        $assignments = $this->assignmentService->getWaiterAssignments($waiterId, $filters, $perPage);

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
    }

    public function show($id): JsonResponse
    {
        $assignment = $this->assignmentService->getAssignment($id);

        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());

        if (!$waiterId || (int) $assignment->waiter_id !== (int) $waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this assignment',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ]);
    }

    public function getPending(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
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
            'data' => WaiterAssignmentResource::collection($assignments),
        ]);
    }

    public function getActive(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
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
            'data' => WaiterAssignmentResource::collection($assignments),
        ]);
    }

    public function getCompleted(Request $request): JsonResponse
    {
        $this->resolveTenant($request);
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $limit = (int) $request->query('limit', 10);
        $assignments = $this->assignmentService->getCompletedAssignments($waiterId, $limit);

        return response()->json([
            'success' => true,
            'data' => WaiterAssignmentResource::collection($assignments),
        ]);
    }

    public function accept(AcceptAssignmentRequest $request, $id): JsonResponse
    {
        $this->resolveTenant($request);
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $assignment = $this->assignmentService->acceptAssignment($id, $waiterId);

        return response()->json([
            'success' => true,
            'message' => 'Assignment accepted successfully',
            'data' => $assignment,
        ]);
    }

    public function reject(RejectAssignmentRequest $request, $id): JsonResponse
    {
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
    }

    public function pickup(Request $request, $id): JsonResponse
    {
        $this->resolveTenant($request);
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user()) ?: auth()->id();
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }

        $assignment = $this->assignmentService->pickupOrder($id, $waiterId);

        return response()->json([
            'success' => true,
            'message' => 'Order picked up successfully',
            'data' => $assignment,
        ]);
    }

    public function startDelivery(Request $request, $id): JsonResponse
    {
        $this->resolveTenant($request);
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user()) ?: auth()->id();

        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }

        $assignment = $this->assignmentService->startDelivery($id, $waiterId);

        return response()->json([
            'success' => true,
            'message' => 'Delivery started successfully',
            'data' => $assignment,
        ]);
    }

    public function deliver(DeliverOrderRequest $request, $id): JsonResponse
    {
        $this->resolveTenant($request);
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user()) ?: auth()->id();
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
    }

    public function failed(FailedDeliveryRequest $request, $id): JsonResponse
    {
        $this->resolveTenant($request);
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user()) ?: auth()->id();
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
    }
}
