<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RestaurantTable;

echo "=== RESTAURANT TABLE QR CODES ===\n\n";

$tables = RestaurantTable::whereIn('table_number', ['1', '2', '3', 'T18', 'T19'])
    ->orderBy('table_number')
    ->get();

foreach ($tables as $table) {
    echo "Table: {$table->table_number}\n";
    echo "  QR Token: {$table->qr_token}\n";
    echo "  Status: {$table->status}\n";
    echo "  Active: " . ($table->is_active ? 'Yes' : 'No') . "\n";
    echo "  Order URL: http://localhost:5173/restaurant-order/{$table->qr_token}\n";
    echo "\n";
}

echo "\n=== CLICK ONE OF THESE LINKS TO TEST ===\n";
$firstTable = $tables->first();
if ($firstTable) {
    echo "http://localhost:5173/restaurant-order/{$firstTable->qr_token}\n";
}
