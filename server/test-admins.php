<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing admin data...\n";

// Test 1: Check if we have any hotel users with admin role
$adminCount = \App\Models\HotelUser::where('role', 'admin')->count();
echo "Hotel admin count: " . $adminCount . "\n";

// Test 2: Check if we have any users at all
$userCount = \App\Models\User::count();
echo "Total users: " . $userCount . "\n";

// Test 3: Test the service
try {
    $service = app(\App\Services\Platform\PlatformHotelService::class);
    $admins = $service->getAllAdmins([], 10);
    echo "Service returned admin count: " . $admins->count() . "\n";
    
    if ($admins->count() > 0) {
        $firstAdmin = $admins->first();
        echo "First admin user: " . ($firstAdmin->user->email ?? 'No user') . "\n";
        echo "First admin hotel: " . ($firstAdmin->hotel->name ?? 'No hotel') . "\n";
    }
} catch (Exception $e) {
    echo "Service error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";