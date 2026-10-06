<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\Waiter\WaiterDashboardService;
use App\Models\Waiter;
use App\Models\User;

// Enable query logging
DB::enableQueryLog();
Cache::flush();

$waiter = Waiter::first();
$user = User::find($waiter->user_id);
if ($user) auth()->login($user);

$service = app(WaiterDashboardService::class);

echo "\n========================================\n";
echo "DETAILED QUERY INSPECTION\n";
echo "========================================\n\n";

DB::flushQueryLog();
$result = $service->getDashboardStats($waiter->id);
$queries = DB::getQueryLog();

echo "Total Queries: " . count($queries) . "\n\n";

// Group and analyze queries
$queryGroups = [];
$duplicates = [];

foreach ($queries as $idx => $query) {
    $sql = $query['query'];
    $time = $query['time'];
    $bindings = $query['bindings'];
    
    // Normalize for duplicate detection
    $normalized = preg_replace('/\d+/', '?', preg_replace('/\'[^\']*\'/', '?', $sql));
    
    if (isset($duplicates[$normalized])) {
        $duplicates[$normalized]['count']++;
        $duplicates[$normalized]['queries'][] = $idx + 1;
    } else {
        $duplicates[$normalized] = [
            'count' => 1,
            'queries' => [$idx + 1],
            'example' => $sql,
            'time' => $time
        ];
    }
    
    // Categorize by table
    if (preg_match('/from\s+`?(\w+)`?/i', $sql, $matches)) {
        $table = $matches[1];
        if (!isset($queryGroups[$table])) {
            $queryGroups[$table] = [];
        }
        $queryGroups[$table][] = [
            'index' => $idx + 1,
            'sql' => $sql,
            'time' => $time,
            'bindings' => $bindings
        ];
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "QUERIES BY TABLE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

foreach ($queryGroups as $table => $tableQueries) {
    echo "📊 {$table} ({" . count($tableQueries) . "} queries)\n";
    foreach ($tableQueries as $q) {
        $shortSql = strlen($q['sql']) > 120 ? substr($q['sql'], 0, 120) . '...' : $q['sql'];
        echo "  #{$q['index']}: [{$q['time']}ms] {$shortSql}\n";
        
        // Check for hotel_id
        if (stripos($q['sql'], 'hotel_id') !== false) {
            echo "       ✓ hotel_id filter present\n";
        } else if (in_array($table, ['delivery_tasks', 'orders', 'waiter_performance', 'waiter_floor_assignments'])) {
            echo "       ⚠ MISSING hotel_id filter on tenant-sensitive table!\n";
        }
    }
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "DUPLICATE QUERIES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$foundDuplicates = false;
foreach ($duplicates as $sql => $info) {
    if ($info['count'] > 1) {
        $foundDuplicates = true;
        echo "⚠ DUPLICATE ({$info['count']}x): Queries #" . implode(', #', $info['queries']) . "\n";
        $shortSql = strlen($info['example']) > 100 ? substr($info['example'], 0, 100) . '...' : $info['example'];
        echo "  {$shortSql}\n\n";
    }
}

if (!$foundDuplicates) {
    echo "✓ No duplicate queries found\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "FULL QUERY LOG\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

foreach ($queries as $idx => $query) {
    echo "Query #" . ($idx + 1) . " [{$query['time']}ms]:\n";
    echo $query['query'] . "\n";
    if (!empty($query['bindings'])) {
        echo "Bindings: " . json_encode($query['bindings']) . "\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "INSPECTION COMPLETE\n";
echo "========================================\n\n";
