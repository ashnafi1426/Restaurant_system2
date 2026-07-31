<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterPerformance extends Model
{
    use HasFactory;
    protected $table = 'waiter_performance';
    protected $fillable = [
        'waiter_id',
        'metric_date',
        'deliveries_assigned',
        'deliveries_accepted',
        'deliveries_rejected',
        'acceptance_rate',
        'deliveries_completed',
        'deliveries_failed',
        'completion_rate',
        'avg_delivery_time_minutes',
        'on_time_deliveries',
        'on_time_rate',
        'guest_rating_avg',
        'rating',
        'total_ratings',
        'notes',
    ];
    protected $casts = [
        'metric_date' => 'date',
        'acceptance_rate' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'on_time_rate' => 'decimal:2',
        'guest_rating_avg' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the waiter
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    /**
     * Calculate and update metrics
     */
    public function updateMetrics(): void
    {
        // Fetch delivery tasks for this waiter on this date
        $assignments = DeliveryTask::where('waiter_id', $this->waiter_id)
            ->whereDate('assigned_at', $this->metric_date)
            ->get();

        $totalAssigned = $assignments->count();
        $totalAccepted = $assignments->where('status', '!=', 'rejected')->count();
        $totalRejected = $assignments->where('status', 'rejected')->count();
        $totalCompleted = $assignments->where('status', 'delivered')->count();
        $totalFailed = $assignments->where('status', 'failed')->count();

        // Calculate rates
        $acceptanceRate = $totalAssigned > 0 ? round(($totalAccepted / $totalAssigned) * 100, 2) : 100;
        $completionRate = $totalAccepted > 0 ? round(($totalCompleted / $totalAccepted) * 100, 2) : 0;
        
        // Calculate average delivery time
        $completedAssignments = $assignments->where('status', 'delivered');
        $avgDeliveryTime = null;
        if ($completedAssignments->count() > 0) {
            $totalTime = $completedAssignments->sum(fn ($a) => $a->getDeliveryTimeMinutes() ?? 0);
            $avgDeliveryTime = round($totalTime / $completedAssignments->count(), 2);
        }
        
        // Calculate rating
        $rating = $this->calculateRating();
        
        $this->update([
            'deliveries_assigned' => $totalAssigned,
            'deliveries_accepted' => $totalAccepted,
            'deliveries_rejected' => $totalRejected,
            'acceptance_rate' => $acceptanceRate,
            'deliveries_completed' => $totalCompleted,
            'deliveries_failed' => $totalFailed,
            'completion_rate' => $completionRate,
            'avg_delivery_time_minutes' => $avgDeliveryTime,
            'rating' => $rating,
        ]);
    }

    /**
     * Calculate performance rating (1-5 scale)
     */
    public function calculateRating(): float
    {
        $assignments = DeliveryTask::where('waiter_id', $this->waiter_id)
            ->whereDate('assigned_at', $this->metric_date)
            ->get();

        if ($assignments->isEmpty()) {
            return 5.0;
        }

        $totalAssigned = $assignments->count();
        $totalCompleted = $assignments->where('status', 'delivered')->count();
        $totalFailed = $assignments->where('status', 'failed')->count();
        $totalRejected = $assignments->where('status', 'rejected')->count();

        // Calculate base rating from completion rate
        $completionRate = $totalAssigned > 0 ? ($totalCompleted / $totalAssigned) : 0;
        $baseRating = $completionRate * 5.0; // 0-5 scale

        // Deduct points for failures and rejections
        $failureDeduction = ($totalFailed * 0.5) + ($totalRejected * 0.25);
        
        $rating = max(0, min(5, $baseRating - $failureDeduction));
        
        return round($rating, 2);
    }

    /**
     * Get performance rating (1-5 stars)
     */
    public function getPerformanceRating(): float
    {
        $scores = [];

        // Acceptance rate (30% weight)
        $scores[] = ($this->acceptance_rate / 100) * 5 * 0.3;

        // Completion rate (30% weight)
        $scores[] = ($this->completion_rate / 100) * 5 * 0.3;

        // On-time rate (20% weight)
        $scores[] = ($this->on_time_rate / 100) * 5 * 0.2;

        // Guest rating (20% weight)
        if ($this->guest_rating_avg) {
            $scores[] = $this->guest_rating_avg * 0.2;
        }

        return round(array_sum($scores), 2);
    }

    /**
     * Scope: Get metrics for waiter
     */
    public function scopeForWaiter($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId);
    }

    /**
     * Scope: Get metrics for date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('metric_date', $date);
    }

    /**
     * Scope: Get metrics for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get today's metrics
     */
    public function scopeToday($query)
    {
        return $query->where('metric_date', today());
    }

    /**
     * Scope: Get recent metrics
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('metric_date', '>=', today()->subDays($days));
    }
}
