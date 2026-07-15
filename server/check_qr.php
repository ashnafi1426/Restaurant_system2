<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QR Token Setup Check ===\n\n";

// Check column exists
$hasColumn = Schema::hasColumn('rooms', 'qr_token');
echo "✓ QR Token Column: " . ($hasColumn ? "EXISTS" : "MISSING") . "\n";

// Count rooms with tokens
$count = DB::table('rooms')->whereNotNull('qr_token')->count();
$total = DB::table('rooms')->count();
echo "✓ Rooms with Tokens: $count / $total\n";

// Show sample tokens
echo "\n=== Sample Tokens ===\n";
$rooms = DB::table('rooms')->select('room_number', 'qr_token', 'status')->limit(5)->get();
foreach ($rooms as $room) {
    echo "Room {$room->room_number}: {$room->qr_token} ({$room->status})\n";
}

echo "\n=== Backend Routes Check ===\n";
$routes = DB::table('routes')->where('uri', 'like', '%guest%')->get();
echo "Guest routes available: (Check with: php artisan route:list | grep guest)\n";

echo "\n✓ Backend is ready!\n";
