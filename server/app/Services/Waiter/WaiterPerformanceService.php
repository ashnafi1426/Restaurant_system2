<?php

namespace App\Services\Waiter;

use App\Models\DeliveryTask;
use App\Models\WaiterPerformance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WaiterPerformanceService
{
    public function getPerformance($waiterId, $date = null): WaiterPerformance
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        return WaiterPerformance::firstOrCreate(
            [
                'waiter_id' => $waiterId,
                'metric_date' => $date,
            ],
            [
                'deliveries_assigned' => 0,
                'deliveries_accepted' => 0,
                'deliveries_rejected' => 0,
                'deliveries_completed' => 0,
                'deliveries_failed' => 0,
                'acceptance_rate' => 100,
                'completion_rate' => 0,
                'avg_delivery_time_minutes' => 0,
                'on_time_deliveries' => 0,
                'on_time_rate' => 100,
                'guest_rating_avg' => null,
                'rating' => 0,
                'total_ratings' => 0,
            ]
        );
    }
    public function getPerformanceHistory($waiterId, $startDate, $endDate, $perPage = 30)
    {
        return WaiterPerformance::where('waiter_id', $waiterId)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->orderBy('metric_date', 'desc')
            ->paginate($perPage);
    }
    public function getAggregatedPerformance($waiterId, $startDate, $endDate): array
    {
        $performances = WaiterPerformance::where('waiter_id', $waiterId)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->get();

        $totalDeliveries = $performances->sum('deliveries_completed');
        $totalFailed = $performances->sum('deliveries_failed');
        $totalAssignments = $performances->sum('deliveries_assigned');

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
            ],
            'total_assignments' => $totalAssignments,
            'total_deliveries' => $totalDeliveries,
            'total_failed' => $totalFailed,
            'total_rejected' => $performances->sum('deliveries_rejected'),
            'completion_rate' => $totalAssignments > 0 
                ? round(($totalDeliveries / $totalAssignments) * 100, 2)
                : 0,
            'failure_rate' => $totalAssignments > 0 
                ? round(($totalFailed / $totalAssignments) * 100, 2)
                : 0,
            'average_delivery_time' => round($performances->avg('avg_delivery_time_minutes'), 2),
            'average_guest_rating' => round($performances->avg('guest_rating_avg'), 2),
            'average_overall_rating' => round($performances->avg('rating'), 2),
            'deliveries_per_day' => $performances->count() > 0 
                ? round($totalDeliveries / $performances->count(), 2)
                : 0,
        ];
    }
    public function getWaiterRanking($date = null, $limit = 10): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $rankings = WaiterPerformance::where('metric_date', $date)
            ->with('waiter')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($performance, $index) {
                return [
                    'rank' => $index + 1,
                    'waiter_id' => $performance->waiter_id,
                    'waiter_name' => $performance->waiter?->name,
                    'total_deliveries' => $performance->deliveries_completed,
                    'failed_deliveries' => $performance->deliveries_failed,
                    'average_delivery_time' => $performance->avg_delivery_time_minutes,
                    'guest_rating' => $performance->guest_rating_avg,
                    'rating' => $performance->rating,
                ];
            })
            ->toArray();

        return $rankings;
    }
    public function getPerformanceTrend($waiterId, $days = 7): array
    {
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today();

        $performances = WaiterPerformance::where('waiter_id', $waiterId)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->orderBy('metric_date', 'asc')
            ->get();

        // Fill in missing days with zeros
        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $performance = $performances->firstWhere('metric_date', $date->format('Y-m-d'));

            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'deliveries' => $performance?->deliveries_completed ?? 0,
                'failed' => $performance?->deliveries_failed ?? 0,
                'average_delivery_time' => $performance?->avg_delivery_time_minutes ?? 0,
                'rating' => $performance?->rating ?? 0,
            ];
        }

        return $trend;
    }
    public function generatePerformanceReport($waiterId, $startDate, $endDate): array
    {
        $performances = WaiterPerformance::where('waiter_id', $waiterId)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->get();

        $waiter = User::find($waiterId);

        $aggregated = $this->getAggregatedPerformance($waiterId, $startDate, $endDate);

        // Get on-time delivery percentage
        $assignments = DeliveryTask::where('waiter_id', $waiterId)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();

        $onTimeCount = 0;
        $avgExpectedDeliveryTime = 30; // minutes, can be configured
        foreach ($assignments as $assignment) {
            $deliveryTime = $assignment->getDeliveryTimeMinutes();
            if ($deliveryTime && $deliveryTime <= $avgExpectedDeliveryTime) {
                $onTimeCount++;
            }
        }

        return [
            'waiter' => [
                'id' => $waiter->id,
                'name' => $waiter->name,
                'employee_code' => $waiter->waiter?->employee_code ?? null,
                'shift' => $waiter->waiter?->shift ?? null,
            ],
            'period' => $aggregated['period'],
            'summary' => [
                'total_assignments' => $aggregated['total_assignments'],
                'total_deliveries' => $aggregated['total_deliveries'],
                'total_failed' => $aggregated['total_failed'],
                'total_rejected' => $aggregated['total_rejected'],
                'completion_rate' => $aggregated['completion_rate'],
                'failure_rate' => $aggregated['failure_rate'],
            ],
            'delivery_metrics' => [
                'average_delivery_time' => $aggregated['average_delivery_time'],
                'on_time_percentage' => $assignments->count() > 0
                    ? round(($onTimeCount / $assignments->count()) * 100, 2)
                    : 0,
                'deliveries_per_day' => $aggregated['deliveries_per_day'],
            ],
            'ratings' => [
                'average_guest_rating' => $aggregated['average_guest_rating'],
                'average_overall_rating' => $aggregated['average_overall_rating'],
            ],
            'daily_breakdown' => $performances->map(function ($p) {
                return [
                    'date' => $p->metric_date->format('Y-m-d'),
                    'deliveries' => $p->deliveries_completed,
                    'failed' => $p->deliveries_failed,
                    'rejected' => $p->deliveries_rejected,
                    'average_delivery_time' => $p->avg_delivery_time_minutes,
                    'guest_rating' => $p->guest_rating_avg,
                    'rating' => $p->rating,
                ];
            })->toArray(),
        ];
    }
    public function compareWaiters(array $waiterIds, $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $comparisons = [];
        foreach ($waiterIds as $waiterId) {
            $performance = WaiterPerformance::where('waiter_id', $waiterId)
                ->where('metric_date', $date)
                ->first();

            $waiter = User::find($waiterId);

            $comparisons[] = [
                'waiter_id' => $waiterId,
                'waiter_name' => $waiter->name,
                'total_deliveries' => $performance?->deliveries_completed ?? 0,
                'failed_deliveries' => $performance?->deliveries_failed ?? 0,
                'average_delivery_time' => $performance?->avg_delivery_time_minutes ?? 0,
                'guest_rating' => $performance?->guest_rating_avg ?? 0,
                'overall_rating' => $performance?->rating ?? 0,
                'completion_rate' => $performance?->completion_rate ?? 0,
            ];
        }

        return $comparisons;
    }
    public function getTeamPerformance($managerIdOrTeamId = null, $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $query = WaiterPerformance::where('metric_date', $date)
            ->with('waiter');

        if ($managerIdOrTeamId) {
            // This would filter by manager or team if we have that relationship
            // For now, just get all
        }

        $performances = $query->get();

        $totalDeliveries = $performances->sum('deliveries_completed');
        $totalFailed = $performances->sum('deliveries_failed');
        $waiterCount = $performances->count();

        return [
            'date' => $date->format('Y-m-d'),
            'total_waiters' => $waiterCount,
            'total_deliveries' => $totalDeliveries,
            'total_failed' => $totalFailed,
            'average_deliveries_per_waiter' => $waiterCount > 0 ? round($totalDeliveries / $waiterCount, 2) : 0,
            'average_delivery_time' => round($performances->avg('avg_delivery_time_minutes'), 2),
            'average_guest_rating' => round($performances->avg('guest_rating_avg'), 2),
            'average_overall_rating' => round($performances->avg('rating'), 2),
            'top_performers' => $performances->sortByDesc('rating')
                ->take(5)
                ->map(function ($p) {
                    return [
                        'waiter_id' => $p->waiter_id,
                        'waiter_name' => $p->waiter?->name,
                        'deliveries' => $p->deliveries_completed,
                        'rating' => $p->rating,
                    ];
                })
                ->toArray(),
        ];
    }
    public function getStatistics($waiterId): array
    {
        $today = Carbon::today();
        $week = $today->copy()->subDays(6);
        $month = $today->copy()->subDays(29);

        $todayPerf = WaiterPerformance::where('waiter_id', $waiterId)
            ->where('metric_date', $today)
            ->first();

        $weekPerf = WaiterPerformance::where('waiter_id', $waiterId)
            ->whereBetween('metric_date', [$week, $today])
            ->get();

        $monthPerf = WaiterPerformance::where('waiter_id', $waiterId)
            ->whereBetween('metric_date', [$month, $today])
            ->get();

        return [
            'today' => [
                'deliveries' => $todayPerf?->deliveries_completed ?? 0,
                'failed' => $todayPerf?->deliveries_failed ?? 0,
                'average_time' => $todayPerf?->avg_delivery_time_minutes ?? 0,
                'rating' => $todayPerf?->rating ?? 0,
            ],
            'this_week' => [
                'deliveries' => $weekPerf->sum('deliveries_completed'),
                'failed' => $weekPerf->sum('deliveries_failed'),
                'average_time' => round($weekPerf->avg('avg_delivery_time_minutes'), 2),
                'average_rating' => round($weekPerf->avg('rating'), 2),
            ],
            'this_month' => [
                'deliveries' => $monthPerf->sum('deliveries_completed'),
                'failed' => $monthPerf->sum('deliveries_failed'),
                'average_time' => round($monthPerf->avg('avg_delivery_time_minutes'), 2),
                'average_rating' => round($monthPerf->avg('rating'), 2),
            ],
        ];
    }
    public function getDeliveryTimeDistribution($waiterId, $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $assignments = DeliveryTask::where('waiter_id', $waiterId)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', $date)
            ->get();

        // Categorize by delivery time ranges
        $ranges = [
            '0-10' => 0,
            '11-20' => 0,
            '21-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '60+' => 0,
        ];

        foreach ($assignments as $assignment) {
            $minutes = $assignment->getDeliveryTimeMinutes();
            if ($minutes === null) continue;

            if ($minutes <= 10) $ranges['0-10']++;
            elseif ($minutes <= 20) $ranges['11-20']++;
            elseif ($minutes <= 30) $ranges['21-30']++;
            elseif ($minutes <= 45) $ranges['31-45']++;
            elseif ($minutes <= 60) $ranges['46-60']++;
            else $ranges['60+']++;
        }

        return $ranges;
    }
    public function rateWaiter($waiterId, $rating, $date = null): WaiterPerformance
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $performance = WaiterPerformance::where('waiter_id', $waiterId)
            ->where('metric_date', $date)
            ->first();

        if (!$performance) {
            throw new \Exception('No performance record found for this waiter on this date');
        }

        // Validate rating
        if ($rating < 0 || $rating > 5) {
            throw new \Exception('Rating must be between 0 and 5');
        }

        $performance->update(['guest_rating_avg' => $rating]);
        $performance->calculateRating();

        return $performance;
    }
    public function getMonthlyAveragePerformance($waiterId, $month = null): array
    {
        $month = $month ? Carbon::parse($month) : Carbon::now();
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $performances = WaiterPerformance::where('waiter_id', $waiterId)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->get();

        if ($performances->isEmpty()) {
            return [
                'month' => $month->format('Y-m'),
                'total_deliveries' => 0,
                'average_delivery_time' => 0,
                'average_rating' => 0,
            ];
        }

        return [
            'month' => $month->format('Y-m'),
            'total_deliveries' => $performances->sum('deliveries_completed'),
            'total_failed' => $performances->sum('deliveries_failed'),
            'average_delivery_time' => round($performances->avg('avg_delivery_time_minutes'), 2),
            'average_rating' => round($performances->avg('rating'), 2),
            'average_guest_rating' => round($performances->avg('guest_rating_avg'), 2),
            'working_days' => $performances->count(),
        ];
    }
}
