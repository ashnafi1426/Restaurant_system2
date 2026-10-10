<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Room;
use App\Models\Floor;
use App\Models\Waiter;
use App\Models\WaiterFloorAssignment;
use App\Models\DeliveryTask;

echo "--- FAILED JOBS ---\n";
$f = \Illuminate\Support\Facades\DB::table('failed_jobs')->find(10);
if ($f) {
    $payload = json_decode($f->payload, true);
    echo "DisplayName: " . ($payload['displayName'] ?? 'N/A') . "\n";
    echo "Exception first line: " . strtok($f->exception, "\n") . "\n";
}

echo "\n--- ROOMS SAMPLE ---\n";
$rooms = Room::take(10)->get(['id', 'room_number', 'floor', 'floor_id', 'hotel_id']);
foreach ($rooms as $r) {
    echo "Room #{$r->id} Num: {$r->room_number} FloorCol: '{$r->floor}' FloorIdCol: '{$r->floor_id}' Hotel: {$r->hotel_id}\n";
}

echo "\n--- FLOORS ---\n";
$floors = Floor::all(['id', 'floor_number', 'name', 'hotel_id', 'is_active']);
foreach ($floors as $f) {
    echo "Floor #{$f->id} Num: {$f->floor_number} Name: {$f->name} Hotel: {$f->hotel_id} Active: {$f->is_active}\n";
}

echo "\n--- WAITERS ---\n";
$waiters = Waiter::with('user')->get();
foreach ($waiters as $w) {
    echo "Waiter #{$w->id} User: {$w->user_id} ({$w->name}) Hotel: {$w->hotel_id} Status: {$w->status} Avail: {$w->availability} Max: {$w->maximum_orders} Curr: {$w->current_orders}\n";
}


echo "\n--- WAITER FLOOR ASSIGNMENTS ---\n";
$wfa = WaiterFloorAssignment::all();
foreach ($wfa as $a) {
    echo "WFA #{$a->id} Waiter: {$a->waiter_id} Floor: {$a->floor_id} Date: '{$a->assignment_date}' Shift: '{$a->shift_id}' Active: {$a->is_active} Status: '{$a->status}'\n";
}

echo "\n--- RECENT DELIVERY TASKS ---\n";
$tasks = DeliveryTask::latest()->take(5)->get();
foreach ($tasks as $t) {
    echo "Task #{$t->id} Order: {$t->order_id} Waiter: '{$t->waiter_id}' Status: {$t->status} Floor: '{$t->floor_id}' Room: '{$t->room_id}' Created: {$t->created_at}\n";
}
