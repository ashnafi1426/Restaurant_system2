<?php

/**
 * RESET: Waiter Workload Counters
 * 
 * Resets current_orders to match actual delivery tasks
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Waiter;
use App\Models\DeliveryTask;

echo "\n" . str_repeat("=", 100) . "\n";
echo "RESET: Waiter Workload Counters\n";
echo str_repeat("=", 100) . "\n\n";

$waiters = Waiter::with('user')->get();

foreach ($waiters as $waiter) {
    $email = $waiter->user->email ?? 'N/A';
    
    echo str_repeat("-", 100) . "\n";
    echo "Waiter: {$email}\n";
    echo "  - Current Orders (before): {$waiter->current_orders}/{$waiter->maximum_orders}\n";
    
    // Count actual active delivery tasks
    $activeCount = DeliveryTask::where('waiter_id', $waiter->id)
        ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
        ->count();
    
    echo "  - Actual Active Deliveries: {$activeCount}\n";
    
    if ($waiter->current_orders !== $activeCount) {
        echo "  - ⚠️ Mismatch detected! Correcting...\n";
        $waiter->update(['current_orders' => $activeCount]);
        echo "  - ✅ Current Orders updated to: {$activeCount}\n";
    } else {
        echo "  - ✅ Already correct\n";
    }
    
    // Also ensure they have capacity
    if ($waiter->maximum_orders < 10) {
        echo "  - ℹ️ Increasing maximum_orders to 10 for better capacity\n";
        $waiter->update(['maximum_orders' => 10]);
    }
    
    echo "\n";
}

echo str_repeat("=", 100) . "\n";
echo "✅ Waiter workload counters have been reset\n";
echo str_repeat("=", 100) . "\n\n";
