<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;

echo "=== RESTAURANT TABLES DEBUG ===\n\n";

// Check raw database count (including soft deletes)
echo "1. Raw database count (SELECT COUNT(*)):\n";
$rawCount = DB::table('restaurant_tables')->count();
echo "   Total records: {$rawCount}\n\n";

// Check soft deleted records
echo "2. Soft deleted records:\n";
$softDeleted = DB::table('restaurant_tables')
    ->whereNotNull('deleted_at')
    ->count();
echo "   Soft deleted: {$softDeleted}\n\n";

// Check active (non-deleted) records
echo "3. Active (non-deleted) records:\n";
$active = DB::table('restaurant_tables')
    ->whereNull('deleted_at')
    ->count();
echo "   Active: {$active}\n\n";

// Check using Eloquent (respects SoftDeletes)
echo "4. Eloquent count (respects SoftDeletes):\n";
$eloquentCount = RestaurantTable::count();
echo "   Count: {$eloquentCount}\n\n";

// Check with trashed
echo "5. Eloquent with trashed:\n";
$withTrashed = RestaurantTable::withTrashed()->count();
echo "   With trashed: {$withTrashed}\n\n";

// Get first 5 records details
echo "6. First 5 active tables:\n";
$tables = RestaurantTable::orderBy('table_number')->take(5)->get();
foreach ($tables as $table) {
    echo "   - ID: {$table->id}, Number: {$table->table_number}, Status: {$table->status}, Active: " . ($table->is_active ? 'Yes' : 'No') . "\n";
}

// Check if there are any filters applied
echo "\n7. Sample query with filters (like API):\n";
$query = RestaurantTable::query();
$query->orderBy('table_number', 'asc');
$paginatedCount = $query->count();
echo "   Count with default query: {$paginatedCount}\n";

// Get pagination result
$paginated = $query->paginate(10);
echo "   Paginated total: {$paginated->total()}\n";
echo "   Paginated count: {$paginated->count()}\n";
echo "   Current page items: " . count($paginated->items()) . "\n";

echo "\n=== END DEBUG ===\n";
