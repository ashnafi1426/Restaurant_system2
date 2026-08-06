<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\KitchenService;
use App\Models\Order;
use App\Models\User;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔍 TESTING CHEF DASHBOARD ORDER VISIBILITY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Step 1: Check orders in database
echo "Step 1: Checking orders in database...\n";
$allOrders = Order::with(['guest', 'room', 'orderItems'])->latest('created_at')->take(5)->get();
echo "Total orders in database: " . $allOrders->count() . "\n\n";

if ($allOrders->count() > 0) {
    echo "Recent orders:\n";
    foreach ($allOrders as $order) {
        echo "  • Order: {$order->order_number}\n";
        echo "    Status: {$order->status}\n";
        echo "    Guest: " . ($order->guest->name ?? 'N/A') . "\n";
        echo "    Room: " . ($order->room->room_number ?? 'N/A') . "\n";
        echo "    Items: " . $order->orderItems->count() . "\n";
        echo "    Total: \${$order->total}\n";
        echo "    Created: {$order->created_at}\n\n";
    }
} else {
    echo "❌ NO ORDERS FOUND IN DATABASE!\n\n";
}

// Step 2: Get pending orders specifically
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Step 2: Checking PENDING orders...\n";
$pendingOrders = Order::where('status', Order::STATUS_PENDING)
    ->with(['guest', 'room', 'orderItems.menuItem'])
    ->latest('order_time')
    ->get();
    
echo "Pending orders count: " . $pendingOrders->count() . "\n\n";

if ($pendingOrders->count() > 0) {
    echo "Pending orders details:\n";
    foreach ($pendingOrders as $order) {
        echo "  ✅ Order: {$order->order_number}\n";
        echo "     Status: {$order->status}\n";
        echo "     Guest: " . ($order->guest->name ?? 'N/A') . "\n";
        echo "     Room: " . ($order->room->room_number ?? 'N/A') . "\n";
        echo "     Total: \${$order->total}\n";
        echo "     Order Time: {$order->order_time}\n\n";
    }
} else {
    echo "❌ NO PENDING ORDERS!\n\n";
}

// Step 3: Test KitchenService
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Step 3: Testing KitchenService...\n";

try {
    // Create a mock user (chef)
    $chef = User::where('role', 'chef')->first();
    
    if (!$chef) {
        echo "⚠️ No chef user found, creating mock...\n";
        $chef = (object)['id' => 'test-chef-id', 'role' => 'chef'];
    } else {
        echo "✅ Using chef: {$chef->name} (ID: {$chef->id})\n";
    }
    
    // Get kitchen service
    $kitchenService = app(KitchenService::class);
    
    // Get orders through service
    $orders = $kitchenService->getKitchenOrders($chef);
    
    echo "\nKitchenService Results:\n";
    echo "  Pending: " . count($orders['pending']) . " orders\n";
    echo "  Preparing: " . count($orders['preparing']) . " orders\n";
    echo "  Ready: " . count($orders['ready']) . " orders\n";
    echo "  Served: " . count($orders['served']) . " orders\n\n";
    
    if (count($orders['pending']) > 0) {
        echo "✅ SUCCESS! Pending orders are visible:\n";
        foreach ($orders['pending'] as $order) {
            echo "  • {$order->order_number} - \${$order->total}\n";
        }
    } else {
        echo "❌ NO PENDING ORDERS returned by KitchenService!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR in KitchenService:\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ TEST COMPLETED\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
