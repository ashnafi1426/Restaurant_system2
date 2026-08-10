<?php

/**
 * COMPREHENSIVE TEST: All Floors Waiter Assignment
 * 
 * Tests orders from different rooms on different floors
 * to verify the entire waiter assignment system
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Room;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Guest;
use App\Models\DeliveryTask;
use App\Events\OrderReadyEvent;

echo "\n" . str_repeat("=", 100) . "\n";
echo "COMPREHENSIVE TEST: Multi-Floor Waiter Assignment System\n";
echo str_repeat("=", 100) . "\n\n";

// Test cases: [room_number => expected_waiter_email]
$testCases = [
    '101' => 'ashenafisileshi7@gmail.com',  // Floor 1, Morning shift
    '103' => 'ashenafisileshi7@gmail.com',  // Floor 1, Morning shift
    '202' => 'kmenge771@gmail.com',         // Floor 2, Morning shift
    '203' => 'kmenge771@gmail.com',         // Floor 2, Morning shift
];

$passed = 0;
$failed = 0;
$results = [];

foreach ($testCases as $roomNumber => $expectedWaiterEmail) {
    echo str_repeat("=", 100) . "\n";
    echo "TEST: Room {$roomNumber}\n";
    echo str_repeat("=", 100) . "\n\n";
    
    // Find the room
    $room = Room::where('room_number', $roomNumber)->first();
    
    if (!$room) {
        echo "❌ SKIP: Room {$roomNumber} not found\n\n";
        $failed++;
        $results[$roomNumber] = ['status' => 'SKIPPED', 'reason' => 'Room not found'];
        continue;
    }
    
    $floor = $room->floor_id ? \App\Models\HotelFloor::find($room->floor_id) : null;
    
    echo "Room {$roomNumber} Details:\n";
    echo "  - Room ID: {$room->id}\n";
    echo "  - Floor (text): " . ($room->floor ?? 'NULL') . "\n";
    echo "  - Floor ID: " . ($room->floor_id ?? 'NULL') . "\n";
    if ($floor) {
        echo "  - Floor Number: {$floor->floor_number}\n";
        echo "  - Floor Name: {$floor->name}\n";
    }
    echo "\n";
    
    // Create test order
    $guest = Guest::first();
    if (!$guest) {
        $guest = Guest::create([
            'name' => 'Test Guest',
            'email' => 'test@example.com',
            'phone' => '1234567890',
        ]);
    }
    
    $menuItem = MenuItem::first();
    if (!$menuItem) {
        echo "❌ ERROR: No menu items found\n\n";
        $failed++;
        $results[$roomNumber] = ['status' => 'ERROR', 'reason' => 'No menu items'];
        continue;
    }
    
    $orderNumber = 'TEST-' . $roomNumber . '-' . time();
    
    try {
        $order = Order::create([
            'order_number' => $orderNumber,
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'order_type' => 'room_service',
            'status' => 'preparing',
            'order_time' => now(),
            'total_amount' => 50.00,
        ]);
        
        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price' => 50.00,
            'item_price_at_order' => 50.00,
            'line_total' => 50.00,
            'subtotal' => 50.00,
        ]);
        
        echo "✅ Order Created: {$orderNumber}\n\n";
        
        // Mark as ready (trigger assignment)
        $order->update(['status' => 'ready']);
        $order->load(['room', 'guest', 'orderItems.menuItem']);
        OrderReadyEvent::dispatch($order);
        
        sleep(1); // Give system time to process
        
        // Check assignment
        $deliveryTask = DeliveryTask::where('order_id', $order->id)->first();
        
        if (!$deliveryTask) {
            echo "❌ FAILED: No DeliveryTask created\n";
            $failed++;
            $results[$roomNumber] = [
                'status' => 'FAILED',
                'reason' => 'No DeliveryTask created',
                'expected' => $expectedWaiterEmail,
                'actual' => null,
            ];
        } else {
            $assignedWaiter = $deliveryTask->waiter;
            $actualWaiterEmail = $assignedWaiter ? $assignedWaiter->user->email : null;
            
            if ($actualWaiterEmail === $expectedWaiterEmail) {
                echo "✅ PASSED: Assigned to correct waiter\n";
                echo "  - Expected: {$expectedWaiterEmail}\n";
                echo "  - Actual: {$actualWaiterEmail}\n";
                echo "  - Task Status: {$deliveryTask->status}\n";
                $passed++;
                $results[$roomNumber] = [
                    'status' => 'PASSED',
                    'expected' => $expectedWaiterEmail,
                    'actual' => $actualWaiterEmail,
                ];
            } else {
                echo "❌ FAILED: Wrong waiter assigned\n";
                echo "  - Expected: {$expectedWaiterEmail}\n";
                echo "  - Actual: " . ($actualWaiterEmail ?? 'NULL') . "\n";
                $failed++;
                $results[$roomNumber] = [
                    'status' => 'FAILED',
                    'reason' => 'Wrong waiter assigned',
                    'expected' => $expectedWaiterEmail,
                    'actual' => $actualWaiterEmail,
                ];
            }
        }
        
        // Cleanup
        if ($deliveryTask) $deliveryTask->delete();
        $order->orderItems()->delete();
        $order->delete();
        
    } catch (\Exception $e) {
        echo "❌ ERROR: {$e->getMessage()}\n";
        $failed++;
        $results[$roomNumber] = [
            'status' => 'ERROR',
            'reason' => $e->getMessage(),
        ];
    }
    
    echo "\n";
}

// Summary
echo str_repeat("=", 100) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 100) . "\n\n";

echo "Total Tests: " . count($testCases) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n\n";

echo "Results:\n";
foreach ($results as $roomNumber => $result) {
    $status = $result['status'];
    $icon = $status === 'PASSED' ? '✅' : '❌';
    
    echo "  {$icon} Room {$roomNumber}: {$status}\n";
    
    if ($status === 'PASSED') {
        echo "      → {$result['actual']}\n";
    } elseif (isset($result['reason'])) {
        echo "      → {$result['reason']}\n";
        if (isset($result['expected'])) {
            echo "      → Expected: {$result['expected']}\n";
            echo "      → Actual: " . ($result['actual'] ?? 'NULL') . "\n";
        }
    }
}

echo "\n";

if ($failed === 0) {
    echo str_repeat("=", 100) . "\n";
    echo "✅ ALL TESTS PASSED! Waiter assignment system is working correctly!\n";
    echo str_repeat("=", 100) . "\n\n";
    exit(0);
} else {
    echo str_repeat("=", 100) . "\n";
    echo "❌ SOME TESTS FAILED! Review the results above.\n";
    echo str_repeat("=", 100) . "\n\n";
    exit(1);
}
