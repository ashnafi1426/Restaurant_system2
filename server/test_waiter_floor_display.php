<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Waiter;

echo "=== TESTING WAITER FLOOR ASSIGNMENTS DISPLAY ===\n\n";

$waiters = Waiter::with([
    'user',
    'floorAssignments' => function ($q) {
        $q->where('assignment_date', '>=', today())
          ->where('status', 'active')
          ->with(['floor', 'shift'])
          ->orderBy('priority');
    }
])->get();

echo "Total Waiters: " . $waiters->count() . "\n\n";

foreach ($waiters as $waiter) {
    $userName = $waiter->user 
        ? "{$waiter->user->first_name} {$waiter->user->last_name}" 
        : 'Unknown';
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Waiter: {$userName}\n";
    echo "ID: {$waiter->id}\n";
    echo "Status: {$waiter->status}\n";
    echo "Section: {$waiter->section}\n";
    echo "Shift: {$waiter->shift}\n";
    echo "\nFloor Assignments: " . $waiter->floorAssignments->count() . "\n";
    
    if ($waiter->floorAssignments->count() > 0) {
        foreach ($waiter->floorAssignments as $assignment) {
            $floorName = $assignment->floor ? $assignment->floor->name : 'Unknown Floor';
            $floorNumber = $assignment->floor ? $assignment->floor->floor_number : 'N/A';
            $shiftName = $assignment->shift ? $assignment->shift->name : 'Unknown Shift';
            $shiftTime = $assignment->shift 
                ? "{$assignment->shift->start_time} - {$assignment->shift->end_time}" 
                : 'N/A';
            
            echo "  ├─ Floor: {$floorName} (#{$floorNumber})\n";
            echo "  ├─ Shift: {$shiftName} ({$shiftTime})\n";
            echo "  ├─ Priority: {$assignment->priority}\n";
            echo "  ├─ Date: {$assignment->assignment_date}\n";
            echo "  └─ Status: {$assignment->status}\n\n";
        }
    } else {
        echo "    NO FLOOR ASSIGNMENTS\n\n";
    }
}

echo "\n=== TESTING API RESPONSE FORMAT ===\n\n";

// Simulate what the controller returns
$formattedWaiters = $waiters->map(function ($waiter) {
    return [
        'id' => $waiter->id,
        'name' => $waiter->user ? "{$waiter->user->first_name} {$waiter->user->last_name}" : 'Unknown',
        'status' => $waiter->status,
        'section' => $waiter->section,
        'shift' => $waiter->shift,
        'floor_assignments' => $waiter->floorAssignments->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'floor_id' => $assignment->floor_id,
                'floor_name' => $assignment->floor->name ?? 'Unknown',
                'floor_number' => $assignment->floor->floor_number ?? 0,
                'shift_id' => $assignment->shift_id,
                'shift_name' => $assignment->shift->name ?? 'Unknown',
                'shift_time' => ($assignment->shift ? "{$assignment->shift->start_time} - {$assignment->shift->end_time}" : 'N/A'),
                'priority' => $assignment->priority,
                'assignment_date' => $assignment->assignment_date,
                'status' => $assignment->status,
            ];
        })->toArray(),
    ];
});

echo "Sample API Response for First Waiter:\n";
echo json_encode($formattedWaiters->first(), JSON_PRETTY_PRINT);
echo "\n\n";

echo "=== CHECK: Floor Names Length ===\n";
foreach ($waiters as $waiter) {
    foreach ($waiter->floorAssignments as $assignment) {
        if ($assignment->floor) {
            $name = $assignment->floor->name;
            $length = strlen($name);
            echo "Floor: '{$name}' (Length: {$length} chars)\n";
        }
    }
}

echo "\n Test Complete\n";
