<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RestaurantTable;

echo "Testing Restaurant Table Creation with new status enum...\n\n";

try {
    // Test 1: Create with 'cleaning' status
    echo "Test 1: Creating table with 'cleaning' status...\n";
    $table1 = RestaurantTable::create([
        'table_number' => 'TEST-CLEAN',
        'table_name' => 'Test Cleaning Table',
        'capacity' => 4,
        'status' => 'cleaning',
        'is_active' => true,
    ]);
    echo "✅ SUCCESS: Created table {$table1->table_number} with status '{$table1->status}'\n\n";
    
    // Test 2: Create with 'out_of_service' status
    echo "Test 2: Creating table with 'out_of_service' status...\n";
    $table2 = RestaurantTable::create([
        'table_number' => 'TEST-OOS',
        'table_name' => 'Test Out of Service',
        'capacity' => 6,
        'status' => 'out_of_service',
        'is_active' => false,
    ]);
    echo "✅ SUCCESS: Created table {$table2->table_number} with status '{$table2->status}'\n\n";
    
    // Test 3: Try old 'maintenance' status (should fail)
    echo "Test 3: Creating table with old 'maintenance' status (should fail)...\n";
    try {
        $table3 = RestaurantTable::create([
            'table_number' => 'TEST-MAINT',
            'table_name' => 'Test Maintenance',
            'capacity' => 4,
            'status' => 'maintenance',
            'is_active' => true,
        ]);
        echo "❌ UNEXPECTED: Table created with 'maintenance' status (should have failed)\n\n";
    } catch (\Exception $e) {
        echo "✅ EXPECTED FAILURE: Cannot create with 'maintenance' status\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "=== Summary ===\n";
    echo "Total tables in database: " . RestaurantTable::count() . "\n";
    echo "Tables with 'cleaning' status: " . RestaurantTable::where('status', 'cleaning')->count() . "\n";
    echo "Tables with 'out_of_service' status: " . RestaurantTable::where('status', 'out_of_service')->count() . "\n";
    
    // Clean up test tables
    echo "\nCleaning up test tables...\n";
    RestaurantTable::whereIn('table_number', ['TEST-CLEAN', 'TEST-OOS', 'TEST-MAINT'])->forceDelete();
    echo "✅ Test tables cleaned up\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest complete!\n";
