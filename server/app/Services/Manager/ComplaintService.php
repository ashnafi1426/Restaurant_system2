<?php

namespace App\Services\Manager;

use App\Models\ComplaintTicket;
use App\Models\ManagerNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    public function createComplaint(array $data): ComplaintTicket
    {
        return DB::transaction(function () use ($data) {
            $complaint = ComplaintTicket::create($data);
            $this->sendComplaintNotification($complaint);
            return $complaint;
        });
    }
    public function getComplaints($filters = [], $perPage = 15)
    {
        $query = ComplaintTicket::query();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by severity
        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by department
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        // Filter by date range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'],
                $filters['end_date'],
            ]);
        }

        // Search in description
        if (!empty($filters['search'])) {
            $query->where('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('ticket_number', 'like', '%' . $filters['search'] . '%');
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->with('guest', 'manager', 'assignedStaff')->paginate($perPage);
    }
    public function getComplaint($id): ComplaintTicket
    {
        return ComplaintTicket::with('guest', 'manager', 'assignedStaff')->findOrFail($id);
    }
    public function assignComplaint($complaintId, $staffId): ComplaintTicket
    {
        return DB::transaction(function () use ($complaintId, $staffId) {
            $complaint = ComplaintTicket::findOrFail($complaintId);
            $complaint->assignTo($staffId);
            \App\Models\ManagerAuditLog::create([
                'manager_id' => auth()->id(),
                'action' => 'assign_complaint',
                'resource_type' => 'complaint',
                'resource_id' => $complaint->id,
                'description' => "Complaint assigned to staff ID: {$staffId}",
            ]);

            return $complaint;
        });
    }
    public function escalateComplaint($complaintId): ComplaintTicket
    {
        return DB::transaction(function () use ($complaintId) {
            $complaint = ComplaintTicket::findOrFail($complaintId);
            $complaint->escalate();

            // Send escalation notification
            ManagerNotification::create([
                'manager_id' => $complaint->manager_id ?? auth()->id(),
                'type' => 'complaint_escalated',
                'title' => 'Complaint Escalated',
                'message' => "Complaint #{$complaint->ticket_number} has been escalated to {$complaint->severity} severity.",
                'priority' => 'urgent',
                'related_id' => $complaint->id,
                'related_type' => 'complaint',
            ]);

            // Log audit
            \App\Models\ManagerAuditLog::create([
                'manager_id' => auth()->id(),
                'action' => 'escalate_complaint',
                'resource_type' => 'complaint',
                'resource_id' => $complaint->id,
                'description' => "Complaint escalated to {$complaint->severity}",
            ]);

            return $complaint;
        });
    }
    public function resolveComplaint($complaintId, $notes): ComplaintTicket
    {
        return DB::transaction(function () use ($complaintId, $notes) {
            $complaint = ComplaintTicket::findOrFail($complaintId);
            $complaint->resolve($notes);
            \App\Models\ManagerAuditLog::create([
                'manager_id' => auth()->id(),
                'action' => 'resolve_complaint',
                'resource_type' => 'complaint',
                'resource_id' => $complaint->id,
                'description' => 'Complaint resolved',
            ]);

            return $complaint;
        });
    }
    public function getStatistics(): array
    {
        $today = Carbon::today();

        return [
            'total_complaints' => ComplaintTicket::count(),
            'open_complaints' => ComplaintTicket::open()->count(),
            'urgent_complaints' => ComplaintTicket::urgent()->count(),
            'today_complaints' => ComplaintTicket::whereDate('created_at', $today)->count(),
            'resolved_today' => ComplaintTicket::where('status', 'resolved')
                ->where('resolved_at', '>=', $today)
                ->count(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
        ];
    }
     public function getComplaintsByType(): array
    {
        return DB::table('complaint_tickets')
            ->select('type', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', Carbon::today()->subDays(30))
            ->groupBy('type')
            ->get()
            ->toArray();
    }
    public function getComplaintsBySeverity(): array
    {
        return DB::table('complaint_tickets')
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', Carbon::today()->subDays(30))
            ->groupBy('severity')
            ->get()
            ->toArray();
    }
    public function getDepartmentPerformance(): array
    {
        return DB::table('complaint_tickets')
            ->select('department', DB::raw('COUNT(*) as complaint_count'))
            ->where('created_at', '>=', Carbon::today()->subDays(30))
            ->groupBy('department')
            ->orderByDesc('complaint_count')
            ->get()
            ->toArray();
    }
    private function getAverageResolutionTime(): ?float
    {
        $avgTime = DB::table('complaint_tickets')
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', Carbon::today()->subDays(7))
            ->average(DB::raw('TIMESTAMPDIFF(HOUR, created_at, resolved_at)'));

        return $avgTime ? round($avgTime, 2) : null;
    }
    private function sendComplaintNotification(ComplaintTicket $complaint): void
    {
        $priority = match ($complaint->severity) {
            'critical' => 'urgent',
            'high' => 'high',
            default => 'normal',
        };

        ManagerNotification::create([
            'manager_id' => $complaint->manager_id ?? auth()->id(),
            'type' => 'new_complaint',
            'title' => 'New Complaint Received',
            'message' => "New {$complaint->severity} complaint of type '{$complaint->type}': {$complaint->description}",
            'priority' => $priority,
            'related_id' => $complaint->id,
            'related_type' => 'complaint',
        ]);
    }
    public function generateComplaintReport($startDate, $endDate): array
    {
        $complaints = ComplaintTicket::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_complaints' => $complaints->count(),
            'resolved_complaints' => $complaints->where('status', 'resolved')->count(),
            'pending_complaints' => $complaints->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'by_type' => $complaints->groupBy('type')->map->count(),
            'by_severity' => $complaints->groupBy('severity')->map->count(),
            'by_department' => $complaints->groupBy('department')->map->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($complaints),
            'avg_satisfaction' => $complaints->whereNotNull('satisfaction_rating')->avg('satisfaction_rating'),
        ];
    }
    private function calculateAverageResolutionTime($complaints): float
    {
        $resolved = $complaints->where('status', 'resolved')->filter(function ($c) {
            return $c->resolved_at && $c->created_at;
        });

        if ($resolved->isEmpty()) {
            return 0;
        }

        $totalHours = $resolved->sum(function ($c) {
            return $c->resolved_at->diffInHours($c->created_at);
        });

        return round($totalHours / $resolved->count(), 2);
    }
}
