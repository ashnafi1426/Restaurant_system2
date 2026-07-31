<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerReport extends Model
{
    use HasFactory;

    protected $table = 'manager_reports';

    protected $fillable = [
        'manager_id',
        'report_type',
        'report_name',
        'report_date',
        'period_start',
        'period_end',
        'total_revenue',
        'daily_revenue',
        'pending_payments',
        'total_orders',
        'completed_orders',
        'failed_orders',
        'total_guests',
        'checked_in_guests',
        'checked_out_guests',
        'total_rooms',
        'occupied_rooms',
        'occupancy_rate',
        'available_rooms',
        'maintenance_rooms',
        'total_complaints',
        'resolved_complaints',
        'pending_complaints',
        'complaint_resolution_rate',
        'staff_on_duty',
        'avg_staff_performance',
        'kitchen_orders',
        'kitchen_completed',
        'kitchen_rejected',
        'avg_prep_time_minutes',
        'summary',
        'charts_data',
        'status',
        'generated_at',
        'sent_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'total_revenue' => 'decimal:2',
        'daily_revenue' => 'decimal:2',
        'pending_payments' => 'decimal:2',
        'occupancy_rate' => 'decimal:2',
        'complaint_resolution_rate' => 'decimal:2',
        'avg_staff_performance' => 'decimal:2',
        'summary' => 'json',
        'charts_data' => 'json',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the manager who generated the report
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Calculate order success rate
     */
    public function getOrderSuccessRate(): float
    {
        if ($this->total_orders === 0) {
            return 0;
        }
        return round(($this->completed_orders / $this->total_orders) * 100, 2);
    }

    /**
     * Calculate average order value
     */
    public function getAverageOrderValue(): float
    {
        if ($this->total_orders === 0) {
            return 0;
        }
        return round($this->total_revenue / $this->total_orders, 2);
    }

    /**
     * Calculate average revenue per guest
     */
    public function getAverageRevenuePerGuest(): float
    {
        if ($this->total_guests === 0) {
            return 0;
        }
        return round($this->total_revenue / $this->total_guests, 2);
    }

    /**
     * Get report status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'generated' => 'Generated',
            'sent' => 'Sent',
            default => 'Unknown',
        };
    }

    /**
     * Mark report as generated
     */
    public function markAsGenerated(): void
    {
        $this->update([
            'status' => 'generated',
            'generated_at' => now(),
        ]);
    }

    /**
     * Mark report as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Scope: Get reports by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope: Get reports by manager
     */
    public function scopeForManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope: Get reports for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }

    /**
     * Scope: Get generated reports
     */
    public function scopeGenerated($query)
    {
        return $query->where('status', '!=', 'draft');
    }

    /**
     * Scope: Get recent reports
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('report_date', 'desc')->limit($limit);
    }
}
