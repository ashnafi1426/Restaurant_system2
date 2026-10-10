<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'ashusle66@gmail.com')->first();
auth()->login($user);

$hotelId = '01a05ff0-93d7-7073-99ec-b3424a1b1090'; // Executive Hotel
app(\App\Services\TenantContext::class)->setHotelId($hotelId);

// Clear cache to test fresh computation
\Illuminate\Support\Facades\Cache::flush();

$service = app(\App\Services\Waiter\WaiterDashboardService::class);
$stats = $service->getDashboardStats(8);

echo "New dashboard stats for Waiter 8 (Executive Hotel):\n";
echo "today_stats: " . json_encode($stats['today_stats'], JSON_PRETTY_PRINT) . "\n";
echo "pending_count: " . $stats['pending_count'] . "\n";
echo "active_count: " . $stats['active_count'] . "\n";
echo "recent_assignments: " . count($stats['recent_assignments']) . "\n";
