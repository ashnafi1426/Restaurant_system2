<?php

/**
 * FIX: Set All Assigned Waiters to Available
 * 
 * This script finds all waiters who have floor assignments for today
 * but are currently set as 'offline', and sets them to 'available'
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Waiter;
use App\Models\WaiterFloorAssignment;

echo "\n" . str_repeat("=", 100) . "\n";
echo "FIX: Set All Assigned Waiters to Available\n";
echo str_repeat("=", 100) . "\n\n";

$today = today()->toDateString();
echo "Date: {$today}\n\n";

// Get all waiters with active floor assignments for today
$assignedWaiters = WaiterFloorAssignment::whereDate('assignment_date', $today)
    ->where('status', 'active')
    ->with(['waiter.user', 'floor', 'shift'])
    ->get()
    ->groupBy('waiter_id');

if ($assignedWaiters->isEmpty()) {
    echo "⚠️ No floor assignments found for today.\n";
    exit(0);
}

echo "Found " . $assignedWaiters->count() . " waiters with floor assignments:\n\n";

$fixedCount = 0;
$alreadyAvailableCount = 0;

foreach ($assignedWaiters as $waiterId => $assignments) {
    $waiter = Waiter::with('user')->find($waiterId);
    
    if (!$waiter) {
        continue;
    }
    
    $email = $waiter->user->email ?? 'N/A';
    $name = $waiter->user->name ?? $email;
    
    // Show waiter info
    echo str_repeat("-", 100) . "\n";
    echo "Waiter: {$name} ({$email})\n";
    echo "  - ID: {$waiter->id}\n";
    echo "  - Status: {$waiter->status}\n";
    echo "  - Availability: {$waiter->availability}\n";
    echo "  - Current Orders: {$waiter->current_orders}/{$waiter->maximum_orders}\n";
    echo "\n";
    
    // Show assignments
    echo "  Floor Assignments Today:\n";
    foreach ($assignments as $assignment) {
        $floorNum = $assignment->floor->floor_number ?? 'N/A';
        $floorName = $assignment->floor->name ?? 'N/A';
        $shiftName = $assignment->shift->name ?? 'N/A';
        
        echo "    - Floor {$floorNum} ({$floorName}) - {$shiftName} shift - Priority: {$assignment->priority}\n";
    }
    echo "\n";
    
    // Check if waiter needs to be fixed
    if ($waiter->status !== 'active') {
        echo "  ⚠️ WARNING: Waiter status is '{$waiter->status}' (not active)\n";
        echo "  Action: Setting status to 'active'...\n";
        $waiter->update(['status' => 'active']);
        echo "  ✅ Status updated to 'active'\n\n";
        $fixedCount++;
    }
    
    if ($waiter->availability !== 'available') {
        echo "  ⚠️ WARNING: Waiter is '{$waiter->availability}' (not available)\n";
        echo "  Action: Setting availability to 'available'...\n";
        $waiter->update(['availability' => 'available']);
        echo "  ✅ Availability updated to 'available'\n\n";
        $fixedCount++;
    } else {
        echo "  ✅ Waiter is already available\n\n";
        $alreadyAvailableCount++;
    }
}

echo str_repeat("=", 100) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 100) . "\n\n";

echo "Total waiters with assignments: " . $assignedWaiters->count() . "\n";
echo "Already available: {$alreadyAvailableCount}\n";
echo "Fixed: {$fixedCount}\n\n";

if ($fixedCount > 0) {
    echo "✅ SUCCESS: {$fixedCount} waiter(s) have been set to available\n\n";
} else {
    echo "ℹ️ All assigned waiters are already available\n\n";
}

echo "RECOMMENDATION:\n";
echo "  Consider adding automatic availability management:\n";
echo "  - When a manager assigns a waiter, prompt to set them as available\n";
echo "  - When a shift starts, automatically set assigned waiters to available\n";
echo "  - When a waiter logs in during their shift, set them to available\n\n";

echo str_repeat("=", 100) . "\n";
