<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RestaurantTable;

echo "=== DIRECT DATABASE QUERY ===\n";
echo "Total tables count: " . RestaurantTable::count() . "\n\n";

echo "=== FIRST 5 TABLES (RAW QUERY) ===\n";
$tables = RestaurantTable::orderBy('table_number')->take(5)->get();
foreach ($tables as $table) {
    echo "ID: {$table->id}\n";
    echo "Table Number: {$table->table_number}\n";
    echo "Capacity: {$table->capacity}\n";
    echo "Status: {$table->status}\n";
    echo "Active: " . ($table->is_active ? 'Yes' : 'No') . "\n";
    echo "Created: {$table->created_at}\n";
    echo "---\n";
}

echo "\n=== SIMULATING CONTROLLER QUERY (No filters) ===\n";
$query = RestaurantTable::query();
$query->orderBy('table_number', 'asc');
$paginatedTables = $query->paginate(15);

echo "Total: {$paginatedTables->total()}\n";
echo "Current Page: {$paginatedTables->currentPage()}\n";
echo "Per Page: {$paginatedTables->perPage()}\n";
echo "Items on this page: " . $paginatedTables->count() . "\n";
echo "\n";

echo "First 3 items:\n";
foreach ($paginatedTables->take(3) as $table) {
    echo "- Table {$table->table_number}: {$table->status}\n";
}
