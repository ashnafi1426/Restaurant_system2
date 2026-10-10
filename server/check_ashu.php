<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'ashusle66@gmail.com')->first();
auth()->login($user);

$hotelId = '01a05ff0-93d7-7073-99ec-b3424a1b1090'; // Executive Hotel
app(\App\Services\TenantContext::class)->setHotelId($hotelId);

$service = app(\App\Services\Waiter\WaiterDashboardService::class);
$readyList = $service->getReadyForPickup(8, 100);
echo "Actual Ready for Pickup count: " . count($readyList) . "\n";
foreach ($readyList as $r) {
    echo "  - " . ($r['order_number'] ?? $r['id']) . " (" . ($r['destination'] ?? 'N/A') . ")\n";
}
