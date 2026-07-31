<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignComplaintRequest;
use App\Http\Requests\CreateComplaintRequest;
use App\Http\Requests\ResolveComplaintRequest;
use App\Http\Resources\ComplaintTicketResource;
use App\Models\ComplaintTicket;
use App\Services\Manager\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager Complaint Controller
 * 
 * Handles complaint ticket management and resolution
 */
class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * Get all complaints with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->query('status'),
                'severity' => $request->query('severity'),
                'type' => $request->query('type'),
                'department' => $request->query('department'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
            ];

            $perPage = $request->query('per_page', 15);
            $complaints = $this->complaintService->getComplaints($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => ComplaintTicketResource::collection($complaints),
                'pagination' => [
                    'total' => $complaints->total(),
                    'per_page' => $complaints->perPage(),
                    'current_page' => $complaints->currentPage(),
                    'last_page' => $complaints->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaints list error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load complaints',
            ], 500);
        }
    }

    /**
     * Get single complaint
     */
    public function show($id): JsonResponse
    {
        try {
            $complaint = $this->complaintService->getComplaint($id);

            return response()->json([
                'success' => true,
                'data' => new ComplaintTicketResource($complaint),
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaint details error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Complaint not found',
            ], 404);
        }
    }

    /**
     * Create new complaint
     */
    public function store(CreateComplaintRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['manager_id'] = auth()->id();

            $complaint = $this->complaintService->createComplaint($data);

            return response()->json([
                'success' => true,
                'data' => new ComplaintTicketResource($complaint),
                'message' => 'Complaint created successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Complaint creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create complaint',
            ], 500);
        }
    }

    /**
     * Assign complaint to staff
     */
    public function assign($id, AssignComplaintRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $complaint = $this->complaintService->assignComplaint($id, $data['assigned_to']);

            return response()->json([
                'success' => true,
                'data' => new ComplaintTicketResource($complaint),
                'message' => 'Complaint assigned successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaint assignment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign complaint',
            ], 500);
        }
    }

    /**
     * Escalate complaint
     */
    public function escalate($id): JsonResponse
    {
        try {
            $complaint = $this->complaintService->escalateComplaint($id);

            return response()->json([
                'success' => true,
                'data' => new ComplaintTicketResource($complaint),
                'message' => 'Complaint escalated successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaint escalation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to escalate complaint',
            ], 500);
        }
    }

    /**
     * Resolve complaint
     */
    public function resolve($id, ResolveComplaintRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $complaint = $this->complaintService->resolveComplaint($id, $data['resolution_notes']);

            if ($request->has('satisfaction_rating')) {
                $complaint->update(['satisfaction_rating' => $request->input('satisfaction_rating')]);
            }

            return response()->json([
                'success' => true,
                'data' => new ComplaintTicketResource($complaint),
                'message' => 'Complaint resolved successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaint resolution error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve complaint',
            ], 500);
        }
    }

    /**
     * Get complaint statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->complaintService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaint statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics',
            ], 500);
        }
    }

    /**
     * Get complaints by type
     */
    public function byType(): JsonResponse
    {
        try {
            $data = $this->complaintService->getComplaintsByType();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaints by type error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load complaint types',
            ], 500);
        }
    }

    /**
     * Get complaints by severity
     */
    public function bySeverity(): JsonResponse
    {
        try {
            $data = $this->complaintService->getComplaintsBySeverity();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaints by severity error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load complaint severity',
            ], 500);
        }
    }

    /**
     * Get department performance
     */
    public function departmentPerformance(): JsonResponse
    {
        try {
            $data = $this->complaintService->getDepartmentPerformance();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Department performance error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load department performance',
            ], 500);
        }
    }

    /**
     * Generate complaint report
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->query('end_date', now()->toDateString());

            $report = $this->complaintService->generateComplaintReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            \Log::error('Complaint report error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
            ], 500);
        }
    }
}
