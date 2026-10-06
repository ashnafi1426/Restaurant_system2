<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\Waiter\WaiterDashboardService;
use App\Models\Waiter;
use App\Models\User;

$waiter = Waiter::first();
$user = User::find($waiter->user_id);
if ($user) auth()->login($user);

$service = app(WaiterDashboardService::class);

echo "\n========================================\n";
echo "ANALYZING getRecentAssignments\n";
echo "========================================\n\n";

DB::enableQueryLog();
DB::flushQueryLog();

$start = microtime(true);
$result = $service->getRecentAssignments($waiter->id, 8);
$time = (microtime(true) - $start) * 1000;

$queries = DB::getQueryLog();

echo "Total Time: " . number_format($time, 2) . " ms\n";
echo "Total Queries: " . count($queries) . "\n";
echo "Result Count: " . count($result) . " items\n\n";

echo "Query Breakdown:\n";
foreach ($queries as $idx => $q) {
    $shortQuery = strlen($q['query']) > 100 ? substr($q['query'], 0, 100) . '...' : $q['query'];
    echo "#" . ($idx + 1) . " [{$q['time']}ms]: {$shortQuery}\n";
    
    if (preg_match('/from\s+`?(\w+)`?/i', $q['query'], $matches)) {
        $table = $matches[1];
        if ($table === 'delivery_tasks') {
            echo "    ^ Main query\n";
        } else {
            echo "    ^ Eager loading: {$table}\n";
        }
    }
}

echo "\nOptimization Opportunities:\n";

// Check if there are any whereHas queries
$whereHasCount = 0;
foreach ($queries as $q) {
    if (stripos($q['query'], 'exists (select') !== false) {
        $whereHasCount++;
    }
}

if ($whereHasCount > 0) {
    echo "  ⚠️  Found {$whereHasCount} whereHas subqueries - consider JOIN instead\n";
}

// Check for duplicate table queries
$tables = [];
foreach ($queries as $q) {
    if (preg_match('/from\s+`?(\w+)`?/i', $q['query'], $matches)) {
        $table = $matches[1];
        $tables[] = $table;
    }
}
$tableCounts = array_count_values($tables);
foreach ($tableCounts as $table => $count) {
    if ($count > 1 && $table !== 'cache') {
        echo "  ⚠️  Table '{$table}' queried {$count} times - possible N+1\n";
    }
}

echo "\n========================================\n\n";
