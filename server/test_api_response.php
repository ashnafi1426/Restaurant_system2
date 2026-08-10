<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RestaurantTable;
use App\Models\User;

echo "Testing API Response Format\n";
echo "============================\n\n";

// Simulate the controller's response
$perPage = 15;
$tables = RestaurantTable::orderBy('table_number', 'asc')->paginate($perPage);

// Add QR code URLs
$tables->getCollection()->transform(function ($table) {
    $table->qr_code_url = $table->qr_code_url;
    return $table;
});

// This is what the controller returns
$response = [
    'success' => true,
    'data' => $tables,
];

echo "Response structure:\n";
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "Number of tables in response: " . count($tables->items()) . "\n";
echo "Total tables in database: " . $tables->total() . "\n";
echo "Current page: " . $tables->currentPage() . "\n";
echo "Last page: " . $tables->lastPage() . "\n\n";

if (count($tables->items()) > 0) {
    echo "First table:\n";
    echo "  ID: " . $tables->items()[0]->id . "\n";
    echo "  Number: " . $tables->items()[0]->table_number . "\n";
    echo "  Status: " . $tables->items()[0]->status . "\n";
} else {
    echo "⚠️  NO TABLES FOUND IN DATABASE!\n";
}
