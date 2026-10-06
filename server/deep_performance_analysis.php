<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\Waiter\WaiterDashboardService;
use App\Models\Waiter;
use App\Models\User;

echo "\n========================================\n";
echo "DEEP PERFORMANCE ANALYSIS\n";
echo "========================================\n\n";

// Find a waiter
$waiter = Waiter::first();
if (!$waiter) {
    echo "❌ No waiter found\n";
    exit(1);
}

$user = User::find($waiter->user_id);
if ($user) auth()->login($user);

$service = app(WaiterDashboardService::class);

// Clear cache for fresh test
Cache::flush();

echo "Testing each dashboard method individually...\n\n";

// Test each method separately
$methods = [
    'getTodayStats',
    'getPerformanceMetrics', 
    'getRecentAssignments' => [8],
    'getPendingCount',
    'getActiveCount',
];

foreach ($methods as $key => $value) {
    if (is_numeric($key)) {
        $methodName = $value;
        $params = [$waiter->id];
    } else {
        $methodName = $key;
        $params = array_merge([$waiter->id], $value);
    }
    
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    $start = microtime(true);
    try {
        $result = $service->$methodName(...$params);
        $time = (microtime(true) - $start) * 1000;
        $queries = count(DB::getQueryLog());
        $queryTime = array_sum(array_column(DB::getQueryLog(), 'time'));
        
        $status = $time < 100 ? '✅' : ($time < 200 ? '⚠️' : '❌');
        echo "{$status} {$methodName}:\n";
        echo "   Time: " . number_format($time, 2) . " ms\n";
        echo "   Queries: {$queries}\n";
        echo "   Query Time: " . number_format($queryTime, 2) . " ms\n";
        echo "   PHP Time: " . number_format($time - $queryTime, 2) . " ms\n";
        
        if ($time > 200) {
            echo "   ⚠️  SLOW METHOD - Needs optimization\n";
            
            // Show slow queries
            $slowQueries = array_filter(DB::getQueryLog(), fn($q) => $q['time'] > 10);
            if (count($slowQueries) > 0) {
                echo "   Slow queries (>10ms):\n";
                foreach ($slowQueries as $sq) {
                    echo "     - {$sq['time']}ms: " . substr($sq['query'], 0, 80) . "...\n";
                }
            }
        }
        echo "\n";
        
    } catch (\Throwable $e) {
        echo "❌ {$methodName}: ERROR - {$e->getMessage()}\n\n";
    }
}

// Now test the full getDashboardStats
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "FULL DASHBOARD TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

DB::flushQueryLog();
DB::enableQueryLog();

$start = microtime(true);
$result = $service->getDashboardStats($waiter->id);
$totalTime = (microtime(true) - $start) * 1000;
$queries = DB::getQueryLog();
$queryCount = count($queries);
$totalQueryTime = array_sum(array_column($queries, 'time'));

echo "Total Time: " . number_format($totalTime, 2) . " ms\n";
echo "Queries: {$queryCount}\n";
echo "Query Time: " . number_format($totalQueryTime, 2) . " ms\n";
echo "PHP Time: " . number_format($totalTime - $totalQueryTime, 2) . " ms\n\n";

// Analyze query distribution
$tableQueries = [];
foreach ($queries as $q) {
    if (preg_match('/from\s+`?(\w+)`?/i', $q['query'], $matches)) {
        $table = $matches[1];
        if (!isset($tableQueries[$table])) {
            $tableQueries[$table] = ['count' => 0, 'time' => 0];
        }
        $tableQueries[$table]['count']++;
        $tableQueries[$table]['time'] += $q['time'];
    }
}

echo "Query distribution by table:\n";
arsort($tableQueries);
foreach ($tableQueries as $table => $stats) {
    echo "  {$table}: {$stats['count']} queries, " . number_format($stats['time'], 2) . " ms\n";
}

echo "\n";

// Check for inefficiencies
echo "Performance Bottlenecks:\n";
if ($totalQueryTime / $totalTime > 0.5) {
    echo "  ⚠️  Database queries taking " . number_format(($totalQueryTime / $totalTime) * 100, 1) . "% of time\n";
} else {
    echo "  ✅ Database queries taking " . number_format(($totalQueryTime / $totalTime) * 100, 1) . "% of time (good)\n";
}

$phpTime = $totalTime - $totalQueryTime;
if ($phpTime > 100) {
    echo "  ⚠️  PHP processing taking {$phpTime}ms (should be <100ms)\n";
    echo "     Possible causes: Complex data transformation, logging, or inefficient PHP code\n";
} else {
    echo "  ✅ PHP processing time acceptable\n";
}

if ($queryCount > 15) {
    echo "  ⚠️  Too many queries ({$queryCount}), target is <15\n";
} else {
    echo "  ✅ Query count acceptable\n";
}

echo "\n========================================\n";
echo "ANALYSIS COMPLETE\n";
echo "========================================\n\n";
