<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Waiter;
use App\Models\Floor;
use App\Models\WaiterFloorAssignment;
use App\Services\TenantContext;

$floor = Floor::find('01a0ea5f-4dd0-729c-b83f-ca5d8308d567');
$hotelId = $floor->hotel_id;
echo "Floor: {$floor->id}, Hotel: {$hotelId}\n";

echo "\n--- ALL WFA for this floor ---\n";
$allWfa = WaiterFloorAssignment::where('floor_id', $floor->id)->get();
foreach ($allWfa as $w) {
    echo "WFA ID: {$w->id}, Waiter: {$w->waiter_id}, Date: {$w->assignment_date}, is_active: {$w->is_active}, status: {$w->status}, shift_id: {$w->shift_id}\n";
    $waiterObj = Waiter::find($w->waiter_id);
    if ($waiterObj) {
        echo "  -> Waiter {$waiterObj->id} ({$waiterObj->name}), status: {$waiterObj->status}, availability: {$waiterObj->availability}, max: {$waiterObj->maximum_orders}, curr: {$waiterObj->current_orders}\n";
    }
}

echo "\n--- Testing query step by step ---\n";
$q1 = Waiter::query()
    ->join('waiter_floor_assignments', 'waiter_floor_assignments.waiter_id', '=', 'waiters.id')
    ->where('waiter_floor_assignments.floor_id', $floor->id);
echo "Step 1 (join & floor_id): " . $q1->count() . " matches\n";

$q2 = (clone $q1)->where(function ($q) {
    $q->where('waiter_floor_assignments.is_active', true)
      ->orWhere('waiter_floor_assignments.status', 'active');
});
echo "Step 2 (is_active or status active): " . $q2->count() . " matches\n";

$q3 = (clone $q2)->where('waiters.status', 'active');
echo "Step 3 (waiter status active): " . $q3->count() . " matches\n";

$q4 = (clone $q3)->where(function ($q) {
    $q->whereNull('waiter_floor_assignments.assignment_date')
      ->orWhereDate('waiter_floor_assignments.assignment_date', today());
});
echo "Step 4 (assignment_date null or today): " . $q4->count() . " matches\n";

$q5 = (clone $q4)->where('waiters.hotel_id', $hotelId);
echo "Step 5 (hotel_id match): " . $q5->count() . " matches\n";

$q6 = (clone $q5)->where(function ($q) {
    $q->where('waiters.availability', '!=', 'offline')
      ->orWhereNull('waiters.availability');
});
echo "Step 6 (availability != offline): " . $q6->count() . " matches\n";

$q7 = (clone $q6)->where(function ($q) {
    $q->whereNull('waiters.maximum_orders')
      ->orWhereRaw('waiters.current_orders < waiters.maximum_orders');
});
echo "Step 7 (current_orders < max): " . $q7->count() . " matches\n";

