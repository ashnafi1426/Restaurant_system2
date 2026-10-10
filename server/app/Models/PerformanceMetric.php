<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $table = 'performance_metrics';

    protected $fillable = [
        'staff_id',
        'department',
        'metric_date',
        'tasks_assigned',
        'tasks_completed',
        'tasks_pending',
        'completion_rate',
        'avg_task_duration_minutes',
        'late_tasks',
        'on_time_rate',
        'quality_score',
        'customer_complaints',
        'satisfaction_rating',
        'orders_prepared',
        'orders_rejected',
        'deliveries_completed',
        'deliveries_failed',
        'rooms_cleaned',
        'inspection_passes',
        'notes',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'completion_rate' => 'decimal:2',
        'on_time_rate' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'satisfaction_rating' => 'decimal:2',
        'notes' => 'json',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function calculateCompletionRate(): void
    {
        if ($this->tasks_assigned > 0) {
            $this->completion_rate = round(($this->tasks_completed / $this->tasks_assigned) * 100, 2);
        }
    }

    public function calculateOnTimeRate(): void
    {
        $completed = $this->tasks_completed;
        if ($completed > 0) {
            $onTimeCount = $completed - $this->late_tasks;
            $this->on_time_rate = round(($onTimeCount / $completed) * 100, 2);
        }
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('metric_date', '>=', now()->subDays($days)->toDateString());
    }

    public function getPerformanceRating(): float
    {
        $scores = [];

        $scores[] = ($this->completion_rate / 100) * 5 * 0.4;

        $scores[] = ($this->on_time_rate / 100) * 5 * 0.3;

        $scores[] = ($this->quality_score / 100) * 5 * 0.2;

        if ($this->satisfaction_rating) {
            $scores[] = $this->satisfaction_rating * 0.1;
        }

        return round(array_sum($scores), 2);
    }
}

