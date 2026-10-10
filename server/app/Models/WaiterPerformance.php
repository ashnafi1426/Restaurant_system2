<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class WaiterPerformance extends Model
{
    use HasFactory, BelongsToTenant;
    protected $table = 'waiter_performance';
    protected $fillable = [
        'hotel_id',
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

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function updateMetrics(): void
    {
        $assignments = DeliveryTask::where('waiter_id', $this->waiter_id)
            ->whereDate('assigned_at', $this->metric_date)
            ->get();

        $totalAssigned = $assignments->count();
        $totalAccepted = $assignments->where('status', '!=', 'rejected')->count();
        $totalRejected = $assignments->where('status', 'rejected')->count();
        $totalCompleted = $assignments->where('status', 'delivered')->count();
        $totalFailed = $assignments->where('status', 'failed')->count();

        $acceptanceRate = $totalAssigned > 0 ? round(($totalAccepted / $totalAssigned) * 100, 2) : 100;
        $completionRate = $totalAccepted > 0 ? round(($totalCompleted / $totalAccepted) * 100, 2) : 0;

        $completedAssignments = $assignments->where('status', 'delivered');
        $avgDeliveryTime = null;
        if ($completedAssignments->count() > 0) {
            $totalTime = $completedAssignments->sum(fn ($a) => $a->getDeliveryTimeMinutes() ?? 0);
            $avgDeliveryTime = round($totalTime / $completedAssignments->count(), 2);
        }

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

        $completionRate = $totalAssigned > 0 ? ($totalCompleted / $totalAssigned) : 0;
        $baseRating = $completionRate * 5.0;

        $failureDeduction = ($totalFailed * 0.5) + ($totalRejected * 0.25);

        $rating = max(0, min(5, $baseRating - $failureDeduction));

        return round($rating, 2);
    }

    public function getPerformanceRating(): float
    {
        $scores = [];

        $scores[] = ($this->acceptance_rate / 100) * 5 * 0.3;

        $scores[] = ($this->completion_rate / 100) * 5 * 0.3;

        $scores[] = ($this->on_time_rate / 100) * 5 * 0.2;

        if ($this->guest_rating_avg) {
            $scores[] = $this->guest_rating_avg * 0.2;
        }

        return round(array_sum($scores), 2);
    }

    public function scopeForWaiter($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('metric_date', $date);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->where('metric_date', today());
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('metric_date', '>=', today()->subDays($days));
    }
}

